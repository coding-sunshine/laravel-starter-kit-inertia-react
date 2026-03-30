<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Indent;
use App\Models\PowerPlant;
use App\Models\Rake;
use App\Models\Wagon;
use DateTimeInterface;
use Illuminate\Support\Facades\Date;
use InvalidArgumentException;

final readonly class ProvisionRakeForIndent
{
    /**
     * Create a rake for an indent (PDF import or manual indent create).
     *
     * @param  ?string  $rakeNumberFromPdf  Trimmed rake sq. from e-Demand; null for manual indents.
     */
    public function handle(Indent $indent, ?string $rakeNumberFromPdf, int $userId): Rake
    {
        if ($indent->rake()->exists()) {
            throw new InvalidArgumentException('A rake already exists for this indent.');
        }

        $rakeNumber = null;
        if ($rakeNumberFromPdf !== null && mb_trim($rakeNumberFromPdf) !== '') {
            $rakeNumber = mb_trim($rakeNumberFromPdf);
            $this->assertRakeNumberFreeForSidingThisMonth($rakeNumber, (int) $indent->siding_id);
        }

        $powerPlant = $this->resolvePowerPlant($indent->destination);

        $loadingDate = $indent->expected_loading_date;
        if ($loadingDate === null) {
            $loadingDate = Date::now()->toDateString();
        } else {
            $loadingDate = $loadingDate instanceof DateTimeInterface
                ? $loadingDate->format('Y-m-d')
                : (string) $loadingDate;
        }

        $wagonCount = (int) ($indent->total_units ?? 0);
        $rakeType = $this->inferRakeType($indent->demanded_stock);

        $rake = new Rake;
        $rake->indent_id = $indent->id;
        $rake->siding_id = $indent->siding_id;
        $rake->rake_number = $rakeNumber;
        $rake->priority_number = (int) $indent->id;
        $rake->loading_date = $loadingDate;
        $rake->rake_type = $rakeType;
        $rake->wagon_count = $wagonCount;
        $rake->loaded_weight_mt = 0;
        $rake->predicted_weight_mt = null;
        $rake->state = 'pending';
        $rake->loading_free_minutes = (int) config('rrmcs.default_free_time_minutes', 180);
        $rake->rr_expected_date = null;
        $rake->placement_time = null;

        if ($powerPlant !== null) {
            $rake->destination = $powerPlant->name;
            $rake->destination_code = $powerPlant->code;
        }

        $rake->created_by = $userId;
        $rake->updated_by = $userId;
        $rake->save();

        if ($wagonCount > 0) {
            for ($i = 1; $i <= $wagonCount; $i++) {
                $wagon = new Wagon;
                $wagon->rake_id = $rake->id;
                $wagon->wagon_number = 'W'.$i;
                $wagon->wagon_sequence = $i;
                $wagon->state = 'pending';
                $wagon->save();
            }
        }

        $indent->update([
            'state' => 'completed',
            'updated_by' => $userId,
        ]);

        return $rake->fresh();
    }

    public function assertRakeNumberFreeForSidingThisMonth(string $rakeNumber, int $sidingId): void
    {
        $existsInMonth = Rake::query()
            ->where('rake_number', $rakeNumber)
            ->where('siding_id', $sidingId)
            ->whereYear('created_at', now()->year)
            ->whereMonth('created_at', now()->month)
            ->exists();

        if ($existsInMonth) {
            throw new InvalidArgumentException('This rake number is already in use this month for this siding.');
        }
    }

    private function resolvePowerPlant(?string $destination): ?PowerPlant
    {
        if ($destination === null || mb_trim($destination) === '') {
            return null;
        }

        $normalized = mb_strtolower(mb_trim($destination));

        $byCode = PowerPlant::query()
            ->where('is_active', true)
            ->whereRaw('LOWER(code) = ?', [$normalized])
            ->first();

        if ($byCode !== null) {
            return $byCode;
        }

        $byName = PowerPlant::query()
            ->where('is_active', true)
            ->whereRaw('LOWER(name) = ?', [$normalized])
            ->first();

        if ($byName !== null) {
            return $byName;
        }

        return PowerPlant::query()
            ->where('is_active', true)
            ->where(function ($query) use ($normalized): void {
                $query->whereRaw('LOWER(name) LIKE ?', ['%'.$normalized.'%'])
                    ->orWhereRaw('LOWER(code) LIKE ?', ['%'.$normalized.'%']);
            })
            ->orderBy('name')
            ->first();
    }

    private function inferRakeType(?string $demandedStock): ?string
    {
        if ($demandedStock === null || mb_trim($demandedStock) === '') {
            return null;
        }

        $token = mb_trim(explode(' ', mb_trim($demandedStock))[0] ?? '');
        if ($token === '' || mb_strlen($token) > 50) {
            return null;
        }

        if (preg_match('/^[A-Za-z0-9\-_.]+$/', $token) !== 1) {
            return null;
        }

        return $token;
    }
}
