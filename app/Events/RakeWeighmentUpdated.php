<?php

declare(strict_types=1);

namespace App\Events;

use App\Models\Rake;
use App\Models\Weighment;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final class RakeWeighmentUpdated implements ShouldBroadcast
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    public function __construct(
        public Rake $rake,
        public Weighment $weighment,
        public string $action
    ) {}

    /**
     * @return array<int, PrivateChannel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('rake-load.'.$this->rake->id),
        ];
    }

    public function broadcastAs(): string
    {
        return 'weighment.updated';
    }

    /**
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        $this->weighment->load(['wagonWeighments']);

        return [
            'rake_id' => $this->rake->id,
            'rake_number' => $this->rake->rake_number,
            'action' => $this->action,
            'weighment' => $this->weighment->toArray(),
            'weighment_time' => $this->weighment->weighment_time,
            'train_speed_kmph' => $this->weighment->train_speed_kmph,
            'total_weight_mt' => $this->weighment->total_weight_mt,
            'status' => $this->weighment->status,
            'attempt_no' => $this->weighment->attempt_no,
            'wagon_weighments' => $this->weighment->wagonWeighments->toArray(),
        ];
    }
}
