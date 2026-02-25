<?php

declare(strict_types=1);

namespace App\Events;

use App\Models\Rake;
use App\Models\RakeWagonLoading;
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

        return [
            'rake_id' => $this->rake->id,
            'rake_number' => $this->rake->rake_number,
            'action' => $this->action,
            'wagon_loading' => $this->wagonLoading->toArray(),
            'wagon_id' => $this->wagonLoading->wagon_id,
            'attempt_no' => $this->wagonLoading->attempt_no,
        ];
    }
}
