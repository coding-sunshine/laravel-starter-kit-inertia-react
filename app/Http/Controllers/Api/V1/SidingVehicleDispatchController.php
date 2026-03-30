<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Siding;
use App\Models\SidingVehicleDispatch;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Throwable;

final class SidingVehicleDispatchController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'start_date' => ['nullable', 'date_format:Y-m-d'],
                'end_date' => ['nullable', 'date_format:Y-m-d'],
                'siding_id' => ['nullable', 'integer', 'exists:sidings,id'],
            ]);

            $user = $request->user();

            if ($user === null) {
                return response()->json(['message' => 'Unauthenticated.'], 401);
            }

            $sidingIds = $user->isSuperAdmin()
                ? Siding::query()->pluck('id')->all()
                : $user->accessibleSidings()->get()->pluck('id')->all();

            $startDate = $validated['start_date'] ?? null;
            $endDate = $validated['end_date'] ?? null;

            if ($startDate === null && $endDate === null) {
                $today = now()->toDateString();

                $startDate = $today;
                $endDate = $today;
            }

            if ($startDate !== null && $endDate === null) {
                $endDate = $startDate;
            }

            if ($endDate !== null && $startDate === null) {
                $startDate = $endDate;
            }

            $query = SidingVehicleDispatch::query()
                ->whereIn('siding_id', $sidingIds)
                ->when($startDate !== null && $endDate !== null, function ($q) use ($startDate, $endDate): void {
                    $q->whereBetween('issued_on', [
                        Carbon::createFromFormat('Y-m-d', $startDate)->startOfDay(),
                        Carbon::createFromFormat('Y-m-d', $endDate)->endOfDay(),
                    ]);
                })
                ->orderByDesc('issued_on');

            if (array_key_exists('siding_id', $validated) && $validated['siding_id'] !== null) {
                $query->where('siding_id', (int) $validated['siding_id']);
            }

            $dispatches = $query->get();

            return response()->json($dispatches->values());
        } catch (Throwable $e) {
            report($e);

            $payload = [
                'message' => $e->getMessage(),
            ];

            if (config('app.debug')) {
                $payload['exception'] = $e::class;
                $payload['file'] = $e->getFile();
                $payload['line'] = $e->getLine();
                $payload['trace'] = collect($e->getTrace())->take(20)->values();
            }

            return response()->json($payload, 500);
        }
    }
}
