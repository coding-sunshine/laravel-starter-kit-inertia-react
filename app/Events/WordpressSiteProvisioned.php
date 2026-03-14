<?php

declare(strict_types=1);

namespace App\Events;

use App\Models\WordpressWebsite;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final class WordpressSiteProvisioned implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public readonly WordpressWebsite $site) {}

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel("user.{$this->site->organization->owner_id}"),
        ];
    }

    public function broadcastWith(): array
    {
        return [
            'site_id' => $this->site->id,
            'title' => $this->site->title,
            'stage' => $this->site->stage,
            'url' => $this->site->url,
        ];
    }
}
