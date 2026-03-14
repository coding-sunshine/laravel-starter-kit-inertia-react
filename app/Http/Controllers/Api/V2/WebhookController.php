<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V2;

use App\Http\Resources\Api\V2\WebhookEndpointResource;
use App\Models\WebhookEndpoint;
use App\Services\TenantContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

final class WebhookController extends BaseApiController
{
    /**
     * List webhook endpoints for the current organization.
     */
    public function index(): AnonymousResourceCollection
    {
        $webhooks = WebhookEndpoint::query()
            ->where('organization_id', TenantContext::id())
            ->get();

        return WebhookEndpointResource::collection($webhooks);
    }

    /**
     * Show a single webhook endpoint.
     */
    public function show(WebhookEndpoint $webhook): JsonResponse
    {
        return $this->responseSuccess(null, new WebhookEndpointResource($webhook));
    }

    /**
     * Create a webhook endpoint.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'url' => ['required', 'url', 'max:500'],
            'events' => ['required', 'array'],
            'events.*' => ['string'],
            'secret' => ['nullable', 'string', 'max:64'],
            'is_active' => ['boolean'],
        ]);

        $webhook = WebhookEndpoint::query()->create([
            ...$validated,
            'organization_id' => TenantContext::id(),
        ]);

        return $this->responseCreated('Webhook endpoint created.', new WebhookEndpointResource($webhook));
    }

    /**
     * Update a webhook endpoint.
     */
    public function update(Request $request, WebhookEndpoint $webhook): JsonResponse
    {
        $validated = $request->validate([
            'url' => ['sometimes', 'url', 'max:500'],
            'events' => ['sometimes', 'array'],
            'events.*' => ['string'],
            'secret' => ['sometimes', 'nullable', 'string', 'max:64'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        $webhook->update($validated);

        return $this->responseSuccess(null, new WebhookEndpointResource($webhook->fresh()));
    }

    /**
     * Delete a webhook endpoint.
     */
    public function destroy(WebhookEndpoint $webhook): JsonResponse
    {
        $webhook->delete();

        return $this->responseDeleted();
    }
}
