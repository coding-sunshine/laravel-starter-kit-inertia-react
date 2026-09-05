<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\DispatchReport;
use App\Models\Siding;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * Paginated DPR (dispatch report) rows for mobile — same backing data as Vehicle Dispatch → DPR tab
 * ({@see DispatchReport}); route name unchanged for legacy clients.
 */
final class SidingVehicleDispatchController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        if ($user === null) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        $validated = $request->validate([
            'page' => ['sometimes', 'integer', 'min:1'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
            'date' => ['sometimes', 'nullable', 'date'],
            'date_from' => ['sometimes', 'nullable', 'date'],
            'date_to' => ['sometimes', 'nullable', 'date'],
            'start_date' => ['sometimes', 'nullable', 'date_format:Y-m-d'],
            'end_date' => ['sometimes', 'nullable', 'date_format:Y-m-d'],
            'siding_id' => ['sometimes', 'nullable', 'integer', 'exists:sidings,id'],
        ]);

        $sidingIds = $user->isSuperAdmin()
            ? Siding::query()->pluck('id')->all()
            : $user->accessibleSidings()->get()->pluck('id')->all();

        $rawSingle = isset($validated['date']) ? (string) $validated['date'] : null;
        $rawFrom = $validated['date_from'] ?? $validated['start_date'] ?? null;
        $rawTo = $validated['date_to'] ?? $validated['end_date'] ?? null;

        $query = DispatchReport::query()
            ->with('siding')
            ->whereIn('siding_id', $sidingIds);

        if ($rawFrom !== null || $rawTo !== null) {
            $from = $rawFrom ?? $rawTo;
            $to = $rawTo ?? $rawFrom;
            $query->whereDate('issued_on', '>=', $from)
                ->whereDate('issued_on', '<=', $to);
        } elseif ($rawSingle !== null) {
            $query->whereDate('issued_on', $rawSingle);
        } else {
            $query->whereDate('issued_on', now()->toDateString());
        }

        if (($validated['siding_id'] ?? null) !== null) {
            $sidingId = (int) $validated['siding_id'];
            abort_unless(in_array($sidingId, $sidingIds, true), 403);

            $query->where('siding_id', $sidingId);
        }

        $page = max(1, (int) ($validated['page'] ?? 1));
        $perPage = min(100, max(1, (int) ($validated['per_page'] ?? 25)));

        $query->orderByDesc('issued_on')
            ->orderBy('id');

        /** @var LengthAwarePaginator<int, DispatchReport> $paginator */
        $paginator = $query->paginate($perPage, ['*'], 'page', $page);
        $paginator->setPath(route('api.v1.siding-vehicle-dispatches.index', [], false));

        return response()->json($paginator);
    }
}
