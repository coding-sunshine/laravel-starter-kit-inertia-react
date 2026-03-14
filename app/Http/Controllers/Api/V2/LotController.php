<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V2;

use App\Http\Resources\Api\V2\LotResource;
use App\Models\Lot;
use App\Services\TenantContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

final class LotController extends BaseApiController
{
    /**
     * List lots. Supports filter, sort, and pagination.
     *
     * Query params: filter[project_id], filter[title_status], sort, per_page
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $lots = QueryBuilder::for(Lot::class)
            ->allowedFilters([
                AllowedFilter::exact('project_id'),
                AllowedFilter::exact('title_status'),
                AllowedFilter::partial('title'),
            ])
            ->allowedSorts(['id', 'title', 'price', 'created_at'])
            ->whereHas('project', fn ($q) => $q->where('organization_id', TenantContext::id()))
            ->paginate($request->integer('per_page', 15))
            ->withQueryString();

        return LotResource::collection($lots);
    }

    /**
     * Show a single lot.
     */
    public function show(Lot $lot): JsonResponse
    {
        return $this->responseSuccess(null, new LotResource($lot));
    }
}
