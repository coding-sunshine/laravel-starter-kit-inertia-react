<?php

declare(strict_types=1);

namespace Database\Seeders\Development;

use App\Models\Organization;
use App\Models\WebhookEndpoint;
use Illuminate\Database\Seeder;

final class WebhookEndpointSeeder extends Seeder
{
    public function run(): void
    {
        if (WebhookEndpoint::query()->exists()) {
            return;
        }

        $organization = Organization::query()->first();

        if ($organization === null) {
            return;
        }

        WebhookEndpoint::query()->create([
            'organization_id' => $organization->id,
            'url' => 'https://example.com/webhooks/fusion',
            'events' => ['contact.created', 'sale.updated'],
            'secret' => 'dev-secret-key',
            'is_active' => true,
        ]);
    }
}
