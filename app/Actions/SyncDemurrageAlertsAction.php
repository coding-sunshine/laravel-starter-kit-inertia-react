<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Alert;
use App\Models\Rake;

final readonly class SyncDemurrageAlertsAction
{
    /**
     * Create or update demurrage alerts for rakes in loading state.
     * Tiers: 60 min (info), 30 min (warning), 0 min (critical).
     * Each alert body includes projected penalty if free time is exceeded.
     * Resolve active demurrage alerts for rakes no longer in any threshold.
     *
     * @param  array<int>  $sidingIds
     */
    public function handle(array $sidingIds): void
    {
        if ($sidingIds === []) {
            return;
        }

        $rate = (float) config('rrmcs.demurrage_rate_per_mt_hour', 50);
        $rakes = Rake::query()
            ->whereIn('siding_id', $sidingIds)
            ->where('state', 'loading')
            ->whereNotNull('loading_start_time')
            ->whereNotNull('free_time_minutes')
            ->get();

        $rakeIdsInDemurrage = [];
        foreach ($rakes as $rake) {
            $end = $rake->loading_start_time->copy()->addMinutes((int) $rake->free_time_minutes);
            $remainingMinutes = (int) \Illuminate\Support\Facades\Date::now()->diffInMinutes($end, false);

            if ($remainingMinutes > 60) {
                continue;
            }

            $weight = $rake->loaded_weight_mt ?? $rake->predicted_weight_mt ?? 0;
            $demurrageHoursIfExceeded = $remainingMinutes <= 0
                ? abs($remainingMinutes) / 60
                : 1;
            $projectedCharge = $demurrageHoursIfExceeded * $weight * $rate;
            $projectedText = $weight > 0
                ? sprintf(' Projected penalty if exceeded: ₹%s (%.1f h × %s MT × ₹%s/MT/h).', number_format($projectedCharge, 2), $demurrageHoursIfExceeded, number_format($weight, 1), number_format($rate, 0))
                : ' Complete loading and close rake before free time ends to avoid demurrage.';

            if ($remainingMinutes <= 0) {
                $type = 'demurrage_0';
                $severity = 'critical';
                $title = "Demurrage: free time exceeded for rake {$rake->rake_number}";
                $body = "Penalty accruing.{$projectedText}";
            } elseif ($remainingMinutes <= 30) {
                $type = 'demurrage_30';
                $severity = 'warning';
                $title = "Demurrage: {$remainingMinutes} min left for rake {$rake->rake_number}";
                $body = "Complete loading and dispatch before free time ends.{$projectedText}";
            } else {
                $type = 'demurrage_60';
                $severity = 'info';
                $title = "Demurrage: {$remainingMinutes} min left for rake {$rake->rake_number}";
                $body = "Possible penalty if loading exceeds free time.{$projectedText}";
            }

            Alert::query()->updateOrCreate(
                [
                    'rake_id' => $rake->id,
                    'type' => $type,
                    'status' => 'active',
                ],
                [
                    'siding_id' => $rake->siding_id,
                    'title' => $title,
                    'body' => $body,
                    'severity' => $severity,
                ]
            );
            $rakeIdsInDemurrage[] = $rake->id;
        }

        Alert::query()
            ->active()
            ->whereIn('type', ['demurrage_0', 'demurrage_30', 'demurrage_60'])
            ->whereNotNull('rake_id')
            ->whereNotIn('rake_id', $rakeIdsInDemurrage)
            ->update([
                'status' => 'resolved',
                'resolved_at' => now(),
            ]);
    }
}
