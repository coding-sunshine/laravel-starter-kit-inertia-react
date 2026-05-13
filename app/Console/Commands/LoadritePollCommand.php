<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Events\WagonWeightUpdated;
use App\Http\Integrations\Loadrite\Requests\GetLoadingEventsRequest;
use App\Models\LoadriteSetting;
use App\Models\Rake;
use App\Models\WagonLoading;
use App\Services\LoadriteTokenManager;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Throwable;

final class LoadritePollCommand extends Command
{
    protected $signature = 'loadrite:poll
                            {--interval=30 : Seconds between polls}
                            {--once : Run a single poll then exit}';

    protected $description = 'Poll Loadrite for weight events and sync to wagon loadings (foreground, no queue needed).';

    public function handle(LoadriteTokenManager $tokenManager): int
    {
        $settings = LoadriteSetting::query()->whereNotNull('siding_id')->get();

        if ($settings->isEmpty()) {
            $this->error('No loadrite_settings rows found. Run loadrite:store-token first.');

            return self::FAILURE;
        }

        $interval = (int) $this->option('interval');
        $once = (bool) $this->option('once');

        $this->info(sprintf(
            'Polling %d siding(s) every %ds. Ctrl+C to stop.',
            $settings->count(),
            $interval,
        ));

        $cursors = [];

        while (true) {
            foreach ($settings as $setting) {
                $sidingId = $setting->siding_id;
                $from = $cursors[$sidingId] ?? now()->subHour()->format('Y-m-d H:i:s');
                $to = now()->format('Y-m-d H:i:s');

                try {
                    $connector = $tokenManager->getConnector($sidingId);
                    $response = $connector->send(new GetLoadingEventsRequest($setting->site_name, $from, $to));

                    if (! $response->successful()) {
                        $this->warn("Siding {$sidingId}: HTTP {$response->status()}");

                        continue;
                    }

                    $body = $response->json() ?? [];
                    $events = $body['data'] ?? [];
                    $synced = 0;
                    $lastTimestamp = $from;

                    foreach ($events as $event) {
                        if ($this->syncEvent($event, $sidingId)) {
                            $synced++;
                        }

                        if (isset($event['Time']) && $event['Time'] > $lastTimestamp) {
                            $lastTimestamp = $event['Time'];
                        }
                    }

                    $cursors[$sidingId] = $lastTimestamp;

                    $this->line(sprintf(
                        '[%s] Siding %d (%s): %d event(s) synced',
                        now()->format('H:i:s'),
                        $sidingId,
                        $setting->site_name,
                        $synced,
                    ));
                } catch (Throwable $e) {
                    $this->error("Siding {$sidingId}: {$e->getMessage()}");
                    Log::error('loadrite:poll error', ['siding_id' => $sidingId, 'error' => $e->getMessage()]);
                }
            }

            if ($once) {
                return self::SUCCESS;
            }

            sleep($interval);
        }
    }

    private function syncEvent(array $event, int $sidingId): bool
    {
        if (! isset($event['Sequence'], $event['Weight'])) {
            return false;
        }

        $rake = Rake::query()
            ->where('siding_id', $sidingId)
            ->whereIn('state', ['loading', 'placed', 'pending'])
            ->has('wagonLoadings')
            ->latest('id')
            ->first();

        if (! $rake) {
            Log::warning('loadrite:poll — no active rake', ['siding_id' => $sidingId, 'event' => $event]);

            return false;
        }

        $wagonLoading = WagonLoading::query()
            ->where('rake_id', $rake->id)
            ->whereHas('wagon', fn ($q) => $q->where('wagon_sequence', (int) $event['Sequence']))
            ->first();

        if (! $wagonLoading) {
            Log::warning('loadrite:poll — no WagonLoading for sequence', [
                'rake_id' => $rake->id,
                'sequence' => $event['Sequence'],
            ]);

            return false;
        }

        if ($wagonLoading->weight_source === 'weighbridge') {
            return false;
        }

        $updates = [
            'loadrite_weight_mt' => $event['Weight'],
            'loadrite_last_synced_at' => now(),
        ];

        if (! $wagonLoading->loadrite_override) {
            $updates['weight_source'] = 'loadrite';
        }

        $wagonLoading->update($updates);

        $refreshed = $wagonLoading->fresh();

        WagonWeightUpdated::dispatch(
            sidingId: $sidingId,
            wagonId: $wagonLoading->wagon_id,
            sequence: $event['Sequence'],
            loadriteWeightMt: (float) $event['Weight'],
            weightSource: $refreshed->weight_source,
            percentage: $wagonLoading->cc_capacity_mt > 0
                ? round(((float) $event['Weight'] / (float) $wagonLoading->cc_capacity_mt) * 100, 1)
                : 0.0,
            status: $refreshed->weight_source === 'weighbridge' ? 'loaded' : 'loading',
        );

        return true;
    }
}
