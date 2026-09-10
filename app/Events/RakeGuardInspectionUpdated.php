<?php

declare(strict_types=1);

namespace App\Events;

use App\Models\GuardInspection;
use App\Models\Rake;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final class RakeGuardInspectionUpdated implements ShouldBroadcast
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    public function __construct(
        public Rake $rake,
        public GuardInspection $guardInspection,
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
        return 'guard-inspection.updated';
    }

    /**
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return [
            'rake_id' => $this->rake->id,
            'rake_number' => $this->rake->rake_number,
            'action' => $this->action,
            'guard_inspection' => $this->guardInspection->toArray(),
            'inspection_time' => $this->guardInspection->inspection_time,
            'movement_permission_time' => $this->guardInspection->movement_permission_time,
            'is_approved' => $this->guardInspection->is_approved,
            'remarks' => $this->guardInspection->remarks,
            'attempt_no' => $this->guardInspection->attempt_no,
        ];
    }
}
