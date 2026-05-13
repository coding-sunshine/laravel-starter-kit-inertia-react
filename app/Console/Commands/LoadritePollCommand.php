<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Actions\SyncLoadriteEvent;
use App\Http\Integrations\Loadrite\Requests\GetLoadingEventsRequest;
use App\Models\LoadriteSetting;
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

    public function handle(LoadriteTokenManager $tokenManager, SyncLoadriteEvent $sync): int
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
                        if ($sync->handle($event, $sidingId)) {
                            $synced++;
                        }

                        if (isset($event['Time']) && $event['Time'] > $lastTimestamp) {
                            $lastTimestamp = $event['Time'];
                        }
                    }

                    $cursors[$sidingId] = $lastTimestamp;

                    $this->line(sprintf(
                        '[%s] Siding %d (%s): %d new event(s) synced',
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
}
