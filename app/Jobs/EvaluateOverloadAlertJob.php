<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Events\WagonOverloadCritical;
use App\Events\WagonOverloadWarning;
use App\Models\User;
use App\Models\WagonLoading;
use App\Notifications\LoadriteOverloadNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;

final class EvaluateOverloadAlertJob implements ShouldQueue
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
        $wagonLoading = WagonLoading::query()
            ->with('wagon')
            ->whereHas('wagon', fn ($q) => $q->where('wagon_number', $this->event['Sequence']))
            ->whereHas('rake', fn ($q) => $q->where('siding_id', $this->sidingId)->whereIn('state', ['loading', 'placed']))
            ->first();

        if (! $wagonLoading || ! $wagonLoading->wagon) {
            return;
        }

        if ($wagonLoading->weight_source === 'weighbridge') {
            return;
        }

        $ccMt = (float) ($wagonLoading->cc_capacity_mt ?? 0);

        if ($ccMt <= 0) {
            Log::warning('Loadrite alert: zero CC for wagon', ['sequence' => $this->event['Sequence']]);

            return;
        }

        $percentage = ($this->event['Weight'] / $ccMt) * 100;
        $wagonId = $wagonLoading->wagon->id;
        $wagonNumber = (string) $this->event['Sequence'];

        if ($percentage >= 100) {
            $this->fireAlert('critical', $wagonId, $wagonNumber, $ccMt, $percentage);
        } elseif ($percentage >= 90) {
            $this->fireAlert('warning', $wagonId, $wagonNumber, $ccMt, $percentage);
        }
    }

    private function fireAlert(string $level, int $wagonId, string $wagonNumber, float $ccMt, float $percentage): void
    {
        $debounceKey = "loadrite:alert:{$wagonId}:{$level}";

        if (Cache::has($debounceKey)) {
            return;
        }

        Cache::put($debounceKey, true, now()->addMinutes(5));

        $weightMt = (float) $this->event['Weight'];

        if ($level === 'warning') {
            WagonOverloadWarning::dispatch($this->sidingId, $wagonId, $wagonNumber, $weightMt, $ccMt, $percentage);
        } else {
            WagonOverloadCritical::dispatch($this->sidingId, $wagonId, $wagonNumber, $weightMt, $ccMt, $percentage);
        }

        $notification = new LoadriteOverloadNotification($level, $wagonId, $wagonNumber, $this->sidingId, $weightMt, $ccMt, $percentage);
        User::chunkById(100, fn ($users) => Notification::send($users, $notification));
    }
}
