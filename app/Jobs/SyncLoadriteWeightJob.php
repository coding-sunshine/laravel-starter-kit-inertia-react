<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\Rake;
use App\Models\WagonLoading;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

final class SyncLoadriteWeightJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 3;

    /**
     * @param  array{Sequence: int, Weight: float, Timestamp: string}  $event
     */
    public function __construct(
        private readonly array $event,
        private readonly int $sidingId,
    ) {}

    public function handle(): void
    {
        $rake = Rake::query()
            ->where('siding_id', $this->sidingId)
            ->whereIn('state', ['loading', 'placed'])
            ->latest('placement_time')
            ->first();

        if (! $rake) {
            Log::warning('Loadrite sync: no active rake at siding', [
                'siding_id' => $this->sidingId,
                'event' => $this->event,
            ]);

            return;
        }

        $wagonLoading = WagonLoading::query()
            ->where('rake_id', $rake->id)
            ->whereHas('wagon', fn ($q) => $q->where('wagon_number', $this->event['Sequence']))
            ->first();

        if (! $wagonLoading) {
            Log::warning('Loadrite sync: no matching WagonLoading for sequence', [
                'rake_id' => $rake->id,
                'sequence' => $this->event['Sequence'],
            ]);

            return;
        }

        if ($wagonLoading->weight_source === 'weighbridge') {
            Log::debug('Loadrite sync: skipping weighbridge record', [
                'wagon_loading_id' => $wagonLoading->id,
            ]);

            return;
        }

        $updates = [
            'loadrite_weight_mt' => $this->event['Weight'],
            'loadrite_last_synced_at' => now(),
        ];

        if (! $wagonLoading->loadrite_override) {
            $updates['weight_source'] = 'loadrite';
        }

        $wagonLoading->update($updates);

        $refreshed = $wagonLoading->fresh();

        \App\Events\WagonWeightUpdated::dispatch(
            sidingId: $this->sidingId,
            wagonId: $wagonLoading->wagon_id,
            sequence: $this->event['Sequence'],
            loadriteWeightMt: (float) $this->event['Weight'],
            weightSource: $refreshed->weight_source,
            percentage: $wagonLoading->cc_capacity_mt > 0
                ? round(($this->event['Weight'] / (float) $wagonLoading->cc_capacity_mt) * 100, 1)
                : 0.0,
            status: $refreshed->weight_source === 'weighbridge' ? 'loaded' : 'loading',
        );
    }
}
