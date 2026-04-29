<?php

declare(strict_types=1);

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final class RakeStatusUpdated implements ShouldBroadcast
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    public function __construct(
        public readonly int $sidingId,
        public readonly int $rakeId,
        public readonly string $status,
        public readonly int $wagonsLoaded,
        public readonly int $wagonCount,
        public readonly ?string $placementTime,
        public readonly ?string $loadingEndTime,
    ) {}

    public function broadcastOn(): array
    {
        return [new PrivateChannel('siding.'.$this->sidingId)];
    }

    public function broadcastAs(): string
    {
        return 'rake.status.updated';
    }

    public function broadcastWith(): array
    {
        return [
            'rake_id' => $this->rakeId,
            'status' => $this->status,
            'wagons_loaded' => $this->wagonsLoaded,
            'wagon_count' => $this->wagonCount,
            'placement_time' => $this->placementTime,
            'loading_end_time' => $this->loadingEndTime,
        ];
    }
}
