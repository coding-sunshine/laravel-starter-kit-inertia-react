<?php

declare(strict_types=1);

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final class WagonOverloadWarning implements ShouldBroadcast
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    public function __construct(
        public readonly int $sidingId,
        public readonly int $wagonId,
        public readonly string $wagonNumber,
        public readonly float $weightMt,
        public readonly float $ccMt,
        public readonly float $percentage,
    ) {}

    public function broadcastOn(): array
    {
        return [new PrivateChannel('siding.'.$this->sidingId)];
    }

    public function broadcastAs(): string
    {
        return 'wagon.overload.warning';
    }

    public function broadcastWith(): array
    {
        return [
            'wagon_id' => $this->wagonId,
            'wagon_number' => $this->wagonNumber,
            'weight_mt' => $this->weightMt,
            'cc_mt' => $this->ccMt,
            'percentage' => $this->percentage,
            'level' => 'warning',
        ];
    }
}
