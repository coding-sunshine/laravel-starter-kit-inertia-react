<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Http\Integrations\Loadrite\Requests\GetLoadingEventsRequest;
use App\Models\LoadriteSetting;
use App\Services\LoadriteTokenManager;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

final class PollLoadriteJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 3;

    public int $timeout = 120;

    public function __construct(private readonly int $sidingId) {}

    public function handle(LoadriteTokenManager $tokenManager): void
    {
        $lockKey = "loadrite:polling:{$this->sidingId}";
        $cursorKey = "loadrite:cursor:{$this->sidingId}";

        $lock = Cache::lock($lockKey, 35);

        if (! $lock->get()) {
            return;
        }

        try {
            $setting = LoadriteSetting::query()
                ->where('siding_id', $this->sidingId)
                ->firstOrFail();

            $site = $setting->site_name;
            $fromLocalTime = Cache::get($cursorKey, now()->subHour()->format('Y-m-d H:i:s'));
            $toLocalTime = now()->format('Y-m-d H:i:s');

            $connector = $tokenManager->getConnector($this->sidingId);

            // Walk all pages in the time window. Without pagination the cursor
            // got stuck whenever > 150 events accrued (API page size) and the
            // poll would replay page 1 forever.
            $lastTimestamp = $fromLocalTime;
            $page = 1;
            $maxPages = 50;

            do {
                $response = $connector->send(new GetLoadingEventsRequest($site, $fromLocalTime, $toLocalTime, $page));

                if (! $response->successful()) {
                    Log::warning('Loadrite poll failed', [
                        'siding_id' => $this->sidingId,
                        'page' => $page,
                        'status' => $response->status(),
                    ]);

                    return;
                }

                $body = $response->json() ?? [];
                $events = $body['data'] ?? [];
                $totalPages = (int) ($body['metaData']['numberOfPages'] ?? 1);

                if (empty($events)) {
                    break;
                }

                foreach ($events as $event) {
                    SyncLoadriteWeightJob::dispatch($event, $this->sidingId)
                        ->onConnection('redis')
                        ->onQueue('loadrite-sync');
                    EvaluateOverloadAlertJob::dispatch($event, $this->sidingId)
                        ->onConnection('redis')
                        ->onQueue('loadrite-alerts');

                    if (isset($event['Time']) && $event['Time'] > $lastTimestamp) {
                        $lastTimestamp = $event['Time'];
                    }
                }

                $page++;
            } while ($page <= $totalPages && $page <= $maxPages);

            Cache::put($cursorKey, $lastTimestamp, now()->addHours(24));
        } finally {
            $lock->release();
        }

        self::dispatch($this->sidingId)
            ->onConnection('redis')
            ->onQueue('loadrite-poll')
            ->delay(now()->addSeconds(30));
    }
}
