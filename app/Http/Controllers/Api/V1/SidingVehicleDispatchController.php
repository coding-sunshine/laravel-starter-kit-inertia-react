<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Siding;
use App\Models\SidingVehicleDispatch;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class SidingVehicleDispatchController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        if ($user === null) {
            abort(401);
        }

        $sidingIds = $user->isSuperAdmin()
            ? Siding::query()->pluck('id')->all()
            : $user->accessibleSidings()->get()->pluck('id')->all();

        $startDate = $request->date('start_date');
        $endDate = $request->date('end_date');

        if ($startDate === null && $endDate === null) {
            $today = now()->toDateString();

            $startDate = $request->date('start_date', $today);
            $endDate = $request->date('end_date', $today);
        }

        if ($startDate !== null && $endDate === null) {
            $endDate = $startDate->copy();
        }

        if ($endDate !== null && $startDate === null) {
            $startDate = $endDate->copy();
        }

        $query = SidingVehicleDispatch::query()
            ->whereIn('siding_id', $sidingIds)
            ->when($startDate !== null && $endDate !== null, function ($q) use ($startDate, $endDate): void {
                $q->whereBetween('issued_on', [
                    $startDate->startOfDay(),
                    $endDate->endOfDay(),
                ]);
            })
            ->orderByDesc('issued_on');

        if ($request->filled('siding_id')) {
            $query->where('siding_id', $request->integer('siding_id'));
        }

        $dispatches = $query->get();

        return response()->json($dispatches->values());
    }
}
