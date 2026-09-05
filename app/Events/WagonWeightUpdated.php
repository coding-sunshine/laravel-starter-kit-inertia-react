<?php

declare(strict_types=1);

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final class WagonWeightUpdated implements ShouldBroadcast
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    public function __construct(
        public readonly int $sidingId,
        public readonly int $wagonId,
        public readonly int $sequence,
        public readonly float $loadriteWeightMt,
        public readonly string $weightSource,
        public readonly float $percentage,
        public readonly string $status,
    ) {}

    public function broadcastOn(): array
    {
        return [new PrivateChannel('siding.'.$this->sidingId)];
    }

    public function broadcastAs(): string
    {
        return 'wagon.weight.updated';
    }

    public function broadcastWith(): array
    {
        return [
            'wagon_id' => $this->wagonId,
            'sequence' => $this->sequence,
            'loadrite_weight_mt' => $this->loadriteWeightMt,
            'weight_source' => $this->weightSource,
            'percentage' => $this->percentage,
            'status' => $this->status,
        ];
    }
}
