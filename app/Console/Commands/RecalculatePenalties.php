<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Actions\ApplyDemurragePenaltyAction;
use App\Models\AppliedPenalty;
use App\Models\PenaltyType;
use App\Models\Rake;
use App\Models\SectionTimer;
use Illuminate\Console\Command;

final class RecalculatePenalties extends Command
{
    protected $signature = 'penalties:recalculate
                            {--dry-run : Calculate and output diff CSV, write nothing to DB}
                            {--from= : Limit to rakes placed on or after YYYY-MM-DD}
                            {--rake= : Single rake ID for testing}';

    protected $description = 'Recalculate demurrage penalties using the corrected Indian Railways formula.';

    public function handle(ApplyDemurragePenaltyAction $action): int
    {
        $isDryRun = (bool) $this->option('dry-run');

        $query = Rake::query()
            ->whereNotNull('placement_time')
            ->whereNotNull('loading_end_time');

        if ($rakeId = $this->option('rake')) {
            $query->where('id', (int) $rakeId);
        }

        if ($from = $this->option('from')) {
            $query->where('placement_time', '>=', $from);
        }

        $rakes = $query->with('siding')->get();

        if ($isDryRun) {
            $this->line('rake_id,rake_number,siding,old_amount,new_amount,delta');
        }

        $freeMinutes = (int) (SectionTimer::query()
            ->where('section_name', 'loading')
            ->value('free_minutes') ?? 300);

        $penaltyType = PenaltyType::query()
            ->where('code', 'DEM')
            ->where('is_active', true)
            ->first();

        foreach ($rakes as $rake) {
            $existing = AppliedPenalty::query()
                ->where('rake_id', $rake->id)
                ->where('meta->source', 'demurrage')
                ->first();

            $oldAmount = $existing ? (float) $existing->amount : 0.0;

            if ($isDryRun) {
                // Calculate without writing to DB
                $newAmount = $this->calculateAmount($rake, $freeMinutes, $penaltyType);

                $this->line(implode(',', [
                    $rake->id,
                    $rake->rake_number,
                    $rake->siding?->name ?? '',
                    number_format($oldAmount, 2, '.', ''),
                    number_format($newAmount, 2, '.', ''),
                    number_format($newAmount - $oldAmount, 2, '.', ''),
                ]));
            } else {
                $result = $action->handle($rake);
                $newAmount = $result ? (float) $result['amount'] : 0.0;

                // Fetch the record that was just upserted and update meta in PHP
                // (avoids JSON path update syntax which is not supported on SQLite)
                $penalty = AppliedPenalty::query()
                    ->where('rake_id', $rake->id)
                    ->where('meta->source', 'demurrage')
                    ->first();

                if ($penalty) {
                    $meta = $penalty->meta ?? [];
                    $meta['recalculated_at'] = now()->toIso8601String();
                    $meta['correction_reason'] = 'formula_fix_2026-04-29';
                    $penalty->update(['meta' => $meta]);
                }
            }
        }

        if (! $isDryRun) {
            $this->info("Recalculated {$rakes->count()} rakes.");
        }

        return Command::SUCCESS;
    }

    private function calculateAmount(Rake $rake, int $freeMinutes, ?PenaltyType $penaltyType): float
    {
        if ($rake->placement_time === null || $rake->loading_end_time === null || $penaltyType === null) {
            return 0.0;
        }

        $totalMinutes = (int) $rake->placement_time->diffInMinutes($rake->loading_end_time);
        $excessMinutes = $totalMinutes - $freeMinutes;

        if ($excessMinutes <= 0) {
            return 0.0;
        }

        $baseRate = (float) ($penaltyType->default_rate ?? 0.0);
        $chargedHours = (int) ceil($excessMinutes / 60);
        $rateMultiplier = match (true) {
            $chargedHours <= 6 => 1,
            $chargedHours <= 12 => 2,
            $chargedHours <= 24 => 3,
            $chargedHours <= 48 => 4,
            default => 6,
        };
        $wagonCount = max(1, (int) $rake->wagon_count);

        return round($chargedHours * $baseRate * $rateMultiplier * $wagonCount, 2);
    }
}
