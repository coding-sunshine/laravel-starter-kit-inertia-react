<?php

declare(strict_types=1);

namespace App\Events;

use App\Models\Rake;
use App\Models\RakeLoad;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final class RakeLoadUpdated implements ShouldBroadcast
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    public function __construct(
        public Rake $rake,
        public RakeLoad $rakeLoad,
        public array $loadState,
        public string $trigger
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
        return 'load.updated';
    }

    /**
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        $this->rakeLoad->load([
            'wagonLoadings.wagon:id,wagon_number,wagon_sequence,tare_weight_mt,pcc_weight_mt,is_unfit',
            'wagonLoadings.loader:id,loader_name,code',
            'guardInspections',
            'weighments.wagonWeighments',
        ]);

        return [
            'rake_id' => $this->rake->id,
            'rake_number' => $this->rake->rake_number,
            'load_state' => $this->loadState,
            'trigger' => $this->trigger,
            'rake_load' => $this->rakeLoad->toArray(),
            'placement_time' => $this->rakeLoad->placement_time,
            'free_time_minutes' => $this->rakeLoad->free_time_minutes,
            'status' => $this->rakeLoad->status,
        ];
    }
}
