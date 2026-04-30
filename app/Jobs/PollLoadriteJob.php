<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Http\Integrations\Loadrite\Requests\GetNewWeightEventsRequest;
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

    public int $timeout = 25;

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

            $connector = $tokenManager->getConnector($this->sidingId);
            $response = $connector->send(new GetNewWeightEventsRequest($site, $fromLocalTime));

            if (! $response->successful()) {
                Log::warning('Loadrite poll failed', [
                    'siding_id' => $this->sidingId,
                    'status' => $response->status(),
                ]);

                return;
            }

            $events = $response->json() ?? [];
            $lastTimestamp = $fromLocalTime;

            foreach ($events as $event) {
                SyncLoadriteWeightJob::dispatch($event, $this->sidingId)->onQueue('loadrite-sync');
                EvaluateOverloadAlertJob::dispatch($event, $this->sidingId)->onQueue('loadrite-alerts');

                if (isset($event['Timestamp']) && $event['Timestamp'] > $lastTimestamp) {
                    $lastTimestamp = $event['Timestamp'];
                }
            }

            Cache::put($cursorKey, $lastTimestamp, now()->addHours(24));
        } finally {
            $lock->release();
        }

        self::dispatch($this->sidingId)
            ->onQueue('loadrite-poll')
            ->delay(now()->addSeconds(30));
    }
}
