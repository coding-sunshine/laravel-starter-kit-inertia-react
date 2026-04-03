<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Indent;
use App\Models\PowerPlant;
use App\Models\Rake;
use App\Models\Wagon;
use Carbon\CarbonInterface;
use DateTimeInterface;
use Illuminate\Support\Facades\Date;
use InvalidArgumentException;

final readonly class ProvisionRakeForIndent
{
    /**
     * Month used for rake uniqueness: indent date, else expected loading date.
     */
    public static function referenceDateFromIndent(Indent $indent): CarbonInterface
    {
        $d = $indent->indent_date ?? $indent->expected_loading_date;
        if ($d === null) {
            throw new InvalidArgumentException('Indent must have an indent date or expected loading date to provision a rake.');
        }

        return Date::parse($d instanceof DateTimeInterface ? $d->format('Y-m-d H:i:s') : (string) $d);
    }

    /**
     * For PDF import before an Indent row exists: parsed Demand Date/Time and/or Expected Loading Date.
     */
    public static function referenceDateFromParsedPdf(?DateTimeInterface $indentDate, ?DateTimeInterface $expectedLoadingDate): CarbonInterface
    {
        $d = $indentDate ?? $expectedLoadingDate;
        if ($d === null) {
            throw new InvalidArgumentException(
                'Unable to determine indent month from PDF. Ensure Demand Date/Time or Expected Loading Date is present.'
            );
        }

        return Date::parse($d->format('Y-m-d H:i:s'));
    }

    /**
     * Create a rake for an indent (PDF import or manual indent create).
     *
     * @param  ?string  $rakeNumber  Trimmed rake sq. from e-Demand (PDF) or manual entry.
     * @param  ?int  $priorityNumber  Optional override; defaults to indent primary key.
     */
    public function handle(Indent $indent, ?string $rakeNumber, int $userId, ?int $priorityNumber = null): Rake
    {
        if ($indent->rake()->exists()) {
            throw new InvalidArgumentException('A rake already exists for this indent.');
        }

        $reference = self::referenceDateFromIndent($indent);

        $rakeNumber = $rakeNumber !== null && mb_trim($rakeNumber) !== '' ? mb_trim($rakeNumber) : null;
        if ($rakeNumber !== null) {
            $this->assertRakeNumberFreeForSidingInIndentMonth($rakeNumber, (int) $indent->siding_id, $reference);
        }

        if ($priorityNumber !== null) {
            $this->assertPriorityNumberFreeForSidingInIndentMonth($priorityNumber, (int) $indent->siding_id, $reference);
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
        $rake->priority_number = $priorityNumber ?? (int) $indent->id;
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

    public function assertRakeNumberFreeForSidingInIndentMonth(string $rakeNumber, int $sidingId, CarbonInterface $reference): void
    {
        $existsInMonth = Rake::query()
            ->where('rake_number', $rakeNumber)
            ->where('siding_id', $sidingId)
            ->whereYear('loading_date', $reference->year)
            ->whereMonth('loading_date', $reference->month)
            ->exists();
        if ($existsInMonth) {
            throw new InvalidArgumentException(
                'This rake number is already in use for this siding in the indent month.'
            );
        }
    }

    public function assertPriorityNumberFreeForSidingInIndentMonth(int $priorityNumber, int $sidingId, CarbonInterface $reference): void
    {
        $existsInMonth = Rake::query()
            ->where('priority_number', $priorityNumber)
            ->where('siding_id', $sidingId)
            ->whereYear('loading_date', $reference->year)
            ->whereMonth('loading_date', $reference->month)
            ->exists();

        if ($existsInMonth) {
            throw new InvalidArgumentException(
                'This rake priority number is already in use for this siding in the indent month.'
            );
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
