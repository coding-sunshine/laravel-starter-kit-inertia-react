<?php

declare(strict_types=1);

namespace App\Events;

use App\Models\CampaignWebsite;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final class CampaignSitePublished implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public readonly CampaignWebsite $campaign) {}

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel("organization.{$this->campaign->organization_id}"),
        ];
    }

    public function broadcastWith(): array
    {
        return [
            'campaign_id' => $this->campaign->id,
            'title' => $this->campaign->title,
            'site_id' => $this->campaign->site_id,
        ];
    }
}
