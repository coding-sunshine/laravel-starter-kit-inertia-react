<?php

declare(strict_types=1);

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Loader;
use App\Services\Dashboard\LoaderOverloadMetricsService;
use App\Support\Dashboard\DashboardFilterResolver;
use App\Support\Dashboard\DashboardWidgetPermissions;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class LoaderOverloadWebController extends Controller
{
    public function __construct(
        private readonly DashboardFilterResolver $filters,
        private readonly LoaderOverloadMetricsService $metrics,
    ) {}

    public function loaders(Request $request): JsonResponse
    {
        $this->assertCanAccessLoaderOverload($request);
        $resolved = $this->filters->resolve($request);
        $perPage = min(50, max(1, (int) $request->query('per_page', 10)));
        $page = max(1, (int) $request->query('page', 1));

        $paginator = $this->metrics->paginateLoadersWithActivity(
            $resolved['filteredSidingIds'],
            $resolved['from'],
            $resolved['to'],
            $resolved['filterContext'],
            $perPage,
            $page,
        );

        return response()->json([
            'filters' => $this->serializeListFilters($resolved),
            'data' => $paginator->items(),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ],
        ]);
    }

    public function loaderShow(Request $request, Loader $loader): JsonResponse
    {
        $this->assertCanAccessLoaderOverload($request);
        $resolved = $this->filters->resolve($request);
        $data = $this->metrics->loaderDetail(
            $loader,
            $resolved['filteredSidingIds'],
            $resolved['from'],
            $resolved['to'],
            $resolved['filterContext'],
        );
        if ($data === null) {
            abort(404);
        }

        return response()->json([
            'filters' => $this->serializeListFilters($resolved),
            'data' => $data,
        ]);
    }

    public function operators(Request $request): JsonResponse
    {
        $this->assertCanAccessLoaderOverload($request);
        $resolved = $this->filters->resolve($request);
        $perPage = min(50, max(1, (int) $request->query('per_page', 10)));
        $page = max(1, (int) $request->query('page', 1));

        $paginator = $this->metrics->paginateOperatorsWithActivity(
            $resolved['filteredSidingIds'],
            $resolved['from'],
            $resolved['to'],
            $resolved['filterContext'],
            $perPage,
            $page,
        );

        return response()->json([
            'filters' => $this->serializeListFilters($resolved),
            'data' => $paginator->items(),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ],
        ]);
    }

    public function operatorShow(Request $request): JsonResponse
    {
        $this->assertCanAccessLoaderOverload($request);
        $resolved = $this->filters->resolve($request);
        $sidingId = (int) $request->query('siding_id', 0);
        $operator = (string) $request->query('operator', '');
        if ($sidingId <= 0 || mb_trim($operator) === '') {
            abort(422, 'siding_id and operator are required.');
        }
        if (! in_array($sidingId, $resolved['filteredSidingIds'], true)) {
            abort(422, 'The selected siding is not in the current filter scope.');
        }

        $data = $this->metrics->operatorDetail(
            $sidingId,
            $operator,
            $resolved['filteredSidingIds'],
            $resolved['from'],
            $resolved['to'],
            $resolved['filterContext'],
        );
        if ($data === null) {
            abort(404);
        }

        return response()->json([
            'filters' => $this->serializeListFilters($resolved),
            'data' => $data,
        ]);
    }

    private function assertCanAccessLoaderOverload(Request $request): void
    {
        $user = $request->user();
        abort_unless($user !== null, 403);
        abort_unless($user->can('bypass-permissions') || $user->hasPermissionTo('sections.dashboard.view'), 403);
        abort_unless(DashboardWidgetPermissions::userCanSeeDashboardSection($user, 'loader-overload'), 403);
    }

    /**
     * @param  array<string, mixed>  $resolved
     * @return array<string, mixed>
     */
    private function serializeListFilters(array $resolved): array
    {
        return [
            'period' => $resolved['period'],
            'from' => $resolved['from']->toDateString(),
            'to' => $resolved['to']->toDateString(),
            'siding_ids' => array_values($resolved['filteredSidingIds']),
            'power_plant' => $resolved['powerPlant'],
            'rake_number' => $resolved['rakeNumber'],
            'loader_id' => $resolved['loaderId'],
            'loader_operator' => $resolved['loaderOperatorName'],
            'underload_threshold' => $resolved['underloadThresholdPercent'],
            'shift' => $resolved['shift'],
            'penalty_type' => $resolved['penaltyTypeId'],
            'rake_penalty_scope' => $resolved['rakePenaltyScope'],
        ];
    }
}
