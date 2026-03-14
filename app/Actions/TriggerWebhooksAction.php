<?php

declare(strict_types=1);

namespace App\Actions;

use App\Jobs\TriggerWebhookJob;
use App\Models\WebhookEndpoint;

final readonly class TriggerWebhooksAction
{
    /**
     * Dispatch webhook jobs for all active endpoints subscribed to the given event.
     *
     * @param  array<string, mixed>  $payload
     */
    public function handle(string $event, array $payload, int $organizationId): void
    {
        $endpoints = WebhookEndpoint::query()
            ->where('organization_id', $organizationId)
            ->where('is_active', true)
            ->get()
            ->filter(fn (WebhookEndpoint $endpoint): bool => in_array($event, $endpoint->events ?? [], true));

        foreach ($endpoints as $endpoint) {
            dispatch(new TriggerWebhookJob($endpoint, $event, $payload));
        }
    }
}
