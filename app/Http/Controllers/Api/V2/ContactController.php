<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V2;

use App\Http\Resources\Api\V2\ContactResource;
use App\Models\Contact;
use App\Services\TenantContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

final class ContactController extends BaseApiController
{
    /**
     * List contacts. Supports filter, sort, and pagination.
     *
     * Query params: filter[first_name], filter[last_name], filter[type], filter[stage], sort, per_page
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $contacts = QueryBuilder::for(Contact::class)
            ->allowedFilters([
                AllowedFilter::partial('first_name'),
                AllowedFilter::partial('last_name'),
                AllowedFilter::exact('type'),
                AllowedFilter::exact('stage'),
            ])
            ->allowedSorts(['id', 'first_name', 'last_name', 'created_at', 'lead_score'])
            ->where('organization_id', TenantContext::id())
            ->paginate($request->integer('per_page', 15))
            ->withQueryString();

        return ContactResource::collection($contacts);
    }

    /**
     * Show a single contact.
     */
    public function show(Contact $contact): JsonResponse
    {
        return $this->responseSuccess(null, new ContactResource($contact));
    }

    /**
     * Create a contact.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['nullable', 'string', 'max:255'],
            'type' => ['nullable', 'string'],
            'stage' => ['nullable', 'string'],
            'lead_score' => ['nullable', 'integer'],
        ]);

        $contact = Contact::query()->create([
            ...$validated,
            'organization_id' => TenantContext::id(),
            'contact_origin' => 'api',
        ]);

        return $this->responseCreated('Contact created.', new ContactResource($contact));
    }

    /**
     * Update a contact.
     */
    public function update(Request $request, Contact $contact): JsonResponse
    {
        $validated = $request->validate([
            'first_name' => ['sometimes', 'string', 'max:255'],
            'last_name' => ['sometimes', 'nullable', 'string', 'max:255'],
            'type' => ['sometimes', 'string'],
            'stage' => ['sometimes', 'string'],
            'lead_score' => ['sometimes', 'nullable', 'integer'],
        ]);

        $contact->update($validated);

        return $this->responseSuccess(null, new ContactResource($contact->fresh()));
    }
}
