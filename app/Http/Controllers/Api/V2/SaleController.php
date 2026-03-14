<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V2;

use App\Http\Resources\Api\V2\SaleResource;
use App\Models\Sale;
use App\Services\TenantContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

final class SaleController extends BaseApiController
{
    /**
     * List sales. Supports filter, sort, and pagination.
     *
     * Query params: filter[status], filter[lot_id], filter[project_id], sort, per_page
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $sales = QueryBuilder::for(Sale::class)
            ->allowedFilters([
                AllowedFilter::exact('status'),
                AllowedFilter::exact('lot_id'),
                AllowedFilter::exact('project_id'),
                AllowedFilter::exact('client_contact_id'),
            ])
            ->allowedSorts(['id', 'status', 'created_at', 'settled_at'])
            ->where('organization_id', TenantContext::id())
            ->paginate($request->integer('per_page', 15))
            ->withQueryString();

        return SaleResource::collection($sales);
    }

    /**
     * Show a single sale.
     */
    public function show(Sale $sale): JsonResponse
    {
        return $this->responseSuccess(null, new SaleResource($sale));
    }

    /**
     * Create a sale.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'client_contact_id' => ['nullable', 'integer', 'exists:contacts,id'],
            'lot_id' => ['nullable', 'integer', 'exists:lots,id'],
            'project_id' => ['nullable', 'integer', 'exists:projects,id'],
            'status' => ['required', 'string'],
        ]);

        $sale = Sale::query()->create([
            ...$validated,
            'organization_id' => TenantContext::id(),
        ]);

        return $this->responseCreated('Sale created.', new SaleResource($sale));
    }

    /**
     * Update a sale.
     */
    public function update(Request $request, Sale $sale): JsonResponse
    {
        $validated = $request->validate([
            'client_contact_id' => ['sometimes', 'nullable', 'integer', 'exists:contacts,id'],
            'lot_id' => ['sometimes', 'nullable', 'integer', 'exists:lots,id'],
            'project_id' => ['sometimes', 'nullable', 'integer', 'exists:projects,id'],
            'status' => ['sometimes', 'string'],
            'settled_at' => ['sometimes', 'nullable', 'date'],
        ]);

        $sale->update($validated);

        return $this->responseSuccess(null, new SaleResource($sale->fresh()));
    }
}
