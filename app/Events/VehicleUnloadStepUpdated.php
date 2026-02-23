<?php

declare(strict_types=1);

namespace App\Events;

use App\Models\VehicleUnload;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final class VehicleUnloadStepUpdated implements ShouldBroadcast
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    public function __construct(
        public VehicleUnload $unload
    ) {}

    /**
     * @return array<int, PrivateChannel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('unload.'.$this->unload->id),
        ];
    }

    public function broadcastAs(): string
    {
        return 'step.updated';
    }

    /**
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        $this->unload->load(['steps' => fn ($q) => $q->orderBy('step_number'), 'weighments']);

        return [
            'unload_id' => $this->unload->id,
            'steps' => $this->unload->steps->toArray(),
            'weighments' => $this->unload->weighments->toArray(),
            'state' => $this->unload->state,
        ];
    }
}
