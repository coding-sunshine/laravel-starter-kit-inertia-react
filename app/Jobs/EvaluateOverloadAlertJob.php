<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Events\WagonOverloadCritical;
use App\Events\WagonOverloadWarning;
use App\Models\Alert;
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
     * @param  array{Id?: string, Event?: string, Weight?: float, Time?: string, Sequence?: int}  $event  Raw Loadrite API event
     */
    public function __construct(
        private readonly array $event,
        private readonly int $sidingId,
    ) {}

    public function handle(): void
    {
        // Loadrite events are matched to a wagon by their UserData-keyed
        // sequence. Most events do not carry a `Sequence` key at all, so
        // there is nothing to evaluate — bail before touching missing keys.
        if (! isset($this->event['Sequence'], $this->event['Weight'])) {
            return;
        }

        $sequence = (int) $this->event['Sequence'];
        $weightMt = (float) $this->event['Weight'];

        $wagonLoading = WagonLoading::query()
            ->with('wagon')
            ->whereHas('wagon', fn ($q) => $q->where('wagon_sequence', $sequence))
            ->whereHas('rake', fn ($q) => $q->where('siding_id', $this->sidingId)->whereIn('state', ['loading', 'placed', 'pending'])->has('wagonLoadings'))
            ->first();

        if (! $wagonLoading || ! $wagonLoading->wagon) {
            return;
        }

        if ($wagonLoading->weight_source === 'weighbridge') {
            return;
        }

        // PCC = Permissible Carrying Capacity, the legal limit from wagon master data.
        // wagon_loading.cc_capacity_mt is often 0/stale; wagons.pcc_weight_mt is canonical.
        $pccMt = (float) ($wagonLoading->wagon->pcc_weight_mt ?? 0);

        if ($pccMt <= 0) {
            Log::warning('Loadrite alert: missing PCC for wagon', [
                'wagon_id' => $wagonLoading->wagon_id,
                'sequence' => $sequence,
            ]);

            return;
        }

        $percentage = ($weightMt / $pccMt) * 100;

        if ($percentage >= 100) {
            $this->fireAlert('critical', $wagonLoading, $pccMt, $percentage, $sequence, $weightMt);
        } elseif ($percentage >= 90) {
            $this->fireAlert('warning', $wagonLoading, $pccMt, $percentage, $sequence, $weightMt);
        }
    }

    private function fireAlert(string $level, WagonLoading $wagonLoading, float $pccMt, float $percentage, int $sequence, float $weightMt): void
    {
        $wagonId = (int) $wagonLoading->wagon_id;
        $wagonNumber = (string) ($wagonLoading->wagon->wagon_number ?? $sequence);
        $debounceKey = "loadrite:alert:{$wagonId}:{$level}";

        if (Cache::has($debounceKey)) {
            return;
        }

        Cache::put($debounceKey, true, now()->addMinutes(5));

        // Persist to the alerts table so /alerts page and Control Room alerts feed pick it up.
        Alert::create([
            'siding_id' => $this->sidingId,
            'rake_id' => $wagonLoading->rake_id,
            'type' => 'wagon.overload',
            'severity' => $level,
            'status' => 'active',
            'title' => $level === 'critical'
                ? "Wagon #{$wagonNumber} critical overload"
                : "Wagon #{$wagonNumber} overload warning",
            'body' => sprintf(
                '%.2f MT loaded vs %.2f MT PCC (%.1f%%).',
                $weightMt,
                $pccMt,
                $percentage,
            ),
        ]);

        if ($level === 'warning') {
            WagonOverloadWarning::dispatch($this->sidingId, $wagonId, $wagonNumber, $weightMt, $pccMt, $percentage);
        } else {
            WagonOverloadCritical::dispatch($this->sidingId, $wagonId, $wagonNumber, $weightMt, $pccMt, $percentage);
        }

        $notification = new LoadriteOverloadNotification($level, $wagonId, $wagonNumber, $this->sidingId, $weightMt, $pccMt, $percentage);
        User::chunkById(100, fn ($users) => Notification::send($users, $notification));
    }
}
