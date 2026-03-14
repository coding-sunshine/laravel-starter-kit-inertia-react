<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V2;

use App\Http\Resources\Api\V2\ProjectResource;
use App\Models\Project;
use App\Services\TenantContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

final class ProjectController extends BaseApiController
{
    /**
     * List projects. Supports filter, sort, and pagination.
     *
     * Query params: filter[title], filter[state], filter[stage], sort, per_page
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $projects = QueryBuilder::for(Project::class)
            ->allowedFilters([
                AllowedFilter::partial('title'),
                AllowedFilter::exact('state'),
                AllowedFilter::exact('stage'),
            ])
            ->allowedSorts(['id', 'title', 'created_at', 'min_price', 'max_price'])
            ->where('organization_id', TenantContext::id())
            ->paginate($request->integer('per_page', 15))
            ->withQueryString();

        return ProjectResource::collection($projects);
    }

    /**
     * Show a single project.
     */
    public function show(Project $project): JsonResponse
    {
        return $this->responseSuccess(null, new ProjectResource($project));
    }
}
