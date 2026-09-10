<?php

declare(strict_types=1);

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Fired for every Loadrite event (Add / Subtract / Short Total / Total)
 * after it is persisted to loadrite_events.
 *
 * Consumed by /control-panel-2 to trigger per-event animations:
 *  - Add event       → bucket-dump pulse on target wagon
 *  - Subtract event  → reverse pulse (dust settling)
 *  - Short Total     → wagon fill animation + bulldozer slide to next slot
 *
 * The legacy /control-room page does NOT listen for this broadcast; it
 * continues to rely on WagonWeightUpdated for its UI updates.
 */
final class LoadriteEventBroadcast implements ShouldBroadcast
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    public function __construct(
        public readonly int $sidingId,
        public readonly ?int $rakeId,
        public readonly ?int $wagonId,
        public readonly ?int $wagonSequence,
        public readonly string $eventId,
        public readonly string $eventType,
        public readonly float $weightMt,
        public readonly ?string $eventTime,
        public readonly ?string $operator,
        public readonly ?string $scaleId,
    ) {}

    public function broadcastOn(): array
    {
        return [new PrivateChannel('siding.'.$this->sidingId)];
    }

    public function broadcastAs(): string
    {
        return 'loadrite.event';
    }

    public function broadcastWith(): array
    {
        return [
            'rake_id' => $this->rakeId,
            'wagon_id' => $this->wagonId,
            'wagon_sequence' => $this->wagonSequence,
            'event_id' => $this->eventId,
            'event_type' => $this->eventType,
            'weight_mt' => $this->weightMt,
            'event_time' => $this->eventTime,
            'operator' => $this->operator,
            'scale_id' => $this->scaleId,
        ];
    }
}
