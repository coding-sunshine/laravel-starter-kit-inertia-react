<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Http\Integrations\Loadrite\Requests\GetDowntimeEventsRequest;
use App\Models\LoadriteDowntimeEvent;
use App\Models\LoadriteSetting;
use App\Services\LoadriteTokenManager;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

final class FetchLoadriteDowntimeJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 3;

    public int $timeout = 60;

    public function __construct(
        private readonly int $sidingId,
        private readonly int $lookbackDays = 7,
    ) {}

    public function handle(LoadriteTokenManager $tokenManager): void
    {
        $setting = LoadriteSetting::query()
            ->where('siding_id', $this->sidingId)
            ->firstOrFail();

        $from = now()->subDays($this->lookbackDays)->format('Y-m-d H:i:s');
        $to = now()->format('Y-m-d H:i:s');

        try {
            $connector = $tokenManager->getConnector($this->sidingId);
            $response = $connector->send(new GetDowntimeEventsRequest($setting->site_name, $from, $to));
        } catch (Throwable $e) {
            Log::warning('loadrite.downtime.fetch.exception', [
                'siding_id' => $this->sidingId,
                'message' => $e->getMessage(),
            ]);

            return;
        }

        if (! $response->successful()) {
            Log::warning('loadrite.downtime.fetch.failed', [
                'siding_id' => $this->sidingId,
                'status' => $response->status(),
            ]);

            return;
        }

        $events = $response->json() ?? [];

        foreach ($events as $event) {
            LoadriteDowntimeEvent::updateOrCreate(
                [
                    'siding_id' => $this->sidingId,
                    'downtime_id' => (string) ($event['DowntimeId'] ?? ''),
                ],
                [
                    'start_local_time' => $event['StartLocalTime'] ?? null,
                    'end_local_time' => $event['EndLocalTime'] ?? null,
                    'duration_minutes' => isset($event['DurationInMinutes']) ? (int) $event['DurationInMinutes'] : null,
                    'reason_name' => $event['ReasonName'] ?? null,
                    'sub_reason_name' => $event['SubReasonName'] ?? null,
                    'equipment_name' => $event['EquipmentName'] ?? null,
                    'raw_payload' => $event,
                ],
            );
        }

        Log::info('loadrite.downtime.fetched', [
            'siding_id' => $this->sidingId,
            'count' => count($events),
        ]);
    }
}
