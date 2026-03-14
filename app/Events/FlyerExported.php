<?php

declare(strict_types=1);

namespace App\Events;

use App\Models\Flyer;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final class FlyerExported implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public readonly Flyer $flyer) {}

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel("organization.{$this->flyer->organization_id}"),
        ];
    }

    public function broadcastWith(): array
    {
        return [
            'flyer_id' => $this->flyer->id,
            'title' => $this->flyer->title,
            'pdf_path' => $this->flyer->pdf_path,
        ];
    }
}
