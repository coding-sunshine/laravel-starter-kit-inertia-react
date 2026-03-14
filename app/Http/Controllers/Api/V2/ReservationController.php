<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V2;

use App\Http\Resources\Api\V2\ReservationResource;
use App\Models\PropertyReservation;
use App\Services\TenantContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

final class ReservationController extends BaseApiController
{
    /**
     * List reservations. Supports filter, sort, and pagination.
     *
     * Query params: filter[stage], filter[lot_id], filter[project_id], sort, per_page
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $reservations = QueryBuilder::for(PropertyReservation::class)
            ->allowedFilters([
                AllowedFilter::exact('stage'),
                AllowedFilter::exact('lot_id'),
                AllowedFilter::exact('project_id'),
                AllowedFilter::exact('primary_contact_id'),
                AllowedFilter::exact('deposit_status'),
            ])
            ->allowedSorts(['id', 'stage', 'created_at'])
            ->where('organization_id', TenantContext::id())
            ->paginate($request->integer('per_page', 15))
            ->withQueryString();

        return ReservationResource::collection($reservations);
    }

    /**
     * Show a single reservation.
     */
    public function show(PropertyReservation $reservation): JsonResponse
    {
        return $this->responseSuccess(null, new ReservationResource($reservation));
    }

    /**
     * Create a reservation.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'primary_contact_id' => ['nullable', 'integer', 'exists:contacts,id'],
            'lot_id' => ['nullable', 'integer', 'exists:lots,id'],
            'project_id' => ['nullable', 'integer', 'exists:projects,id'],
            'stage' => ['required', 'string'],
            'purchase_price' => ['nullable', 'numeric'],
            'deposit_status' => ['nullable', 'string'],
        ]);

        $reservation = PropertyReservation::query()->create([
            ...$validated,
            'organization_id' => TenantContext::id(),
        ]);

        return $this->responseCreated('Reservation created.', new ReservationResource($reservation));
    }

    /**
     * Update a reservation.
     */
    public function update(Request $request, PropertyReservation $reservation): JsonResponse
    {
        $validated = $request->validate([
            'primary_contact_id' => ['sometimes', 'nullable', 'integer', 'exists:contacts,id'],
            'lot_id' => ['sometimes', 'nullable', 'integer', 'exists:lots,id'],
            'stage' => ['sometimes', 'string'],
            'purchase_price' => ['sometimes', 'nullable', 'numeric'],
            'deposit_status' => ['sometimes', 'string'],
        ]);

        $reservation->update($validated);

        return $this->responseSuccess(null, new ReservationResource($reservation->fresh()));
    }
}
