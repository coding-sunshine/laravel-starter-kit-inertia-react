<?php

declare(strict_types=1);

namespace App\Events;

use App\Enums\LiveWagonStatus;
use App\Models\Rake;
use App\Models\RakeWagonLoading;
use App\Services\LiveMonitor\WagonStatusResolver;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final class RakeWagonLoadingUpdated implements ShouldBroadcast
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    public function __construct(
        public Rake $rake,
        public RakeWagonLoading $wagonLoading,
        public string $action
    ) {}

    /**
     * Broadcasts on two channels:
     *  - rake-load.{rakeId}  : existing per-rake consumers (rake loader workflow page)
     *  - siding.{sidingId}   : Control Room dashboard subscribes per visible siding
     *
     * @return array<int, PrivateChannel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('rake-load.'.$this->rake->id),
            new PrivateChannel('siding.'.$this->rake->siding_id),
        ];
    }

    public function broadcastAs(): string
    {
        return 'wagon-loading.updated';
    }

    /**
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        $this->wagonLoading->load([
            'wagon:id,wagon_number,wagon_sequence,tare_weight_mt,pcc_weight_mt,is_unfit',
            'loader:id,loader_name,code',
        ]);

        $status = $this->resolveStatus();

        return [
            'rake_id' => $this->rake->id,
            'siding_id' => $this->rake->siding_id,
            'rake_number' => $this->rake->rake_number,
            'action' => $this->action,
            'wagon_loading' => $this->wagonLoading->toArray(),
            'wagon_id' => $this->wagonLoading->wagon_id,
            'live_status' => $status->value,
            'live_status_label' => $status->label(),
            'live_status_color' => $status->color(),
            'broadcast_at' => now()->toIso8601String(),
        ];
    }

    private function resolveStatus(): LiveWagonStatus
    {
        $wagon = $this->wagonLoading->wagon;
        if ($wagon === null) {
            return LiveWagonStatus::Empty;
        }

        return app(WagonStatusResolver::class)->resolve($wagon, $this->wagonLoading);
    }
}
