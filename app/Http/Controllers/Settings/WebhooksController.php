<?php

declare(strict_types=1);

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\StoreWebhookEndpointRequest;
use App\Http\Requests\Settings\UpdateWebhookEndpointRequest;
use App\Models\WebhookEndpoint;
use App\Services\TenantContext;
use App\Services\WebhookDispatcher;
use Harris21\Fuse\CircuitBreaker;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

final class WebhooksController extends Controller
{
    public function index(): Response
    {
        $organization = TenantContext::get();
        abort_unless($organization, 404);

        $endpoints = WebhookEndpoint::withoutGlobalScopes()
            ->where('organization_id', $organization->id)
            ->latest()
            ->get()
            ->map(function (WebhookEndpoint $endpoint): array {
                $breaker = new CircuitBreaker("webhook-{$endpoint->id}");

                $circuitState = 'healthy';
                if ($breaker->isOpen()) {
                    $circuitState = 'tripped';
                } elseif ($breaker->isHalfOpen()) {
                    $circuitState = 'recovering';
                }

                return [
                    'id' => $endpoint->id,
                    'url' => $endpoint->url,
                    'events' => $endpoint->events,
                    'is_active' => $endpoint->is_active,
                    'description' => $endpoint->description,
                    'last_called_at' => $endpoint->last_called_at?->toIso8601String(),
                    'circuit_state' => $circuitState,
                    'created_at' => $endpoint->created_at->toIso8601String(),
                ];
            });

        return Inertia::render('settings/webhooks/index', [
            'endpoints' => $endpoints,
            'eventGroups' => Inertia::once(fn (): array => config('webhooks.events', [])),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('settings/webhooks/create', [
            'eventGroups' => config('webhooks.events', []),
        ]);
    }

    public function store(StoreWebhookEndpointRequest $request): RedirectResponse
    {
        $organization = TenantContext::get();
        abort_unless($organization, 404);

        WebhookEndpoint::withoutGlobalScopes()->create([
            'organization_id' => $organization->id,
            'url' => $request->validated('url'),
            'events' => $request->validated('events'),
            'description' => $request->validated('description'),
            'is_active' => $request->validated('is_active', true),
            'secret' => Str::random(32),
            'created_by' => $request->user()?->id,
        ]);

        return to_route('settings.webhooks.index')->with('success', 'Webhook endpoint created.');
    }

    public function edit(WebhookEndpoint $webhook): Response
    {
        $organization = TenantContext::get();
        abort_if(! $organization || $webhook->organization_id !== $organization->id, 403);

        return Inertia::render('settings/webhooks/edit', [
            'endpoint' => [
                'id' => $webhook->id,
                'url' => $webhook->url,
                'events' => $webhook->events,
                'is_active' => $webhook->is_active,
                'description' => $webhook->description,
            ],
            'eventGroups' => config('webhooks.events', []),
        ]);
    }

    public function update(UpdateWebhookEndpointRequest $request, WebhookEndpoint $webhook): RedirectResponse
    {
        $organization = TenantContext::get();
        abort_if(! $organization || $webhook->organization_id !== $organization->id, 403);

        $webhook->update($request->validated());

        return to_route('settings.webhooks.index')->with('success', 'Webhook endpoint updated.');
    }

    public function destroy(WebhookEndpoint $webhook): RedirectResponse
    {
        $organization = TenantContext::get();
        abort_if(! $organization || $webhook->organization_id !== $organization->id, 403);

        $webhook->delete();

        return to_route('settings.webhooks.index')->with('success', 'Webhook endpoint deleted.');
    }

    public function testPing(WebhookEndpoint $webhook, WebhookDispatcher $dispatcher): JsonResponse
    {
        $organization = TenantContext::get();
        abort_if(! $organization || $webhook->organization_id !== $organization->id, 403);

        $result = $dispatcher->testPing($webhook);

        return response()->json($result);
    }

    public function resetCircuit(WebhookEndpoint $webhook): RedirectResponse
    {
        $organization = TenantContext::get();
        abort_if(! $organization || $webhook->organization_id !== $organization->id, 403);

        $breaker = new CircuitBreaker("webhook-{$webhook->id}");
        $breaker->reset();

        return back()->with('success', 'Circuit breaker reset. Delivery will resume.');
    }

    public function regenerateSecret(WebhookEndpoint $webhook): RedirectResponse
    {
        $organization = TenantContext::get();
        abort_if(! $organization || $webhook->organization_id !== $organization->id, 403);

        $webhook->update(['secret' => Str::random(32)]);

        return back()->with('success', 'Webhook secret regenerated.');
    }
}
