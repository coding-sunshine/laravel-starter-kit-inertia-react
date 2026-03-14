<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\BulkUpdateReservationsAction;
use App\DataTables\PropertyReservationDataTable;
use App\Models\PropertyReservation;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final class PropertyReservationController extends Controller
{
    public function index(Request $request): Response
    {
        return Inertia::render('reservations/index', PropertyReservationDataTable::inertiaProps($request));
    }

    public function quickEdit(Request $request, PropertyReservation $reservation): JsonResponse
    {
        $validated = $request->validate([
            'stage' => ['sometimes', 'string', 'max:100'],
            'deposit_status' => ['sometimes', 'string', 'max:100'],
            'notes' => ['sometimes', 'nullable', 'string', 'max:2000'],
        ]);

        $reservation->update($validated);

        return response()->json(['success' => true, 'id' => $reservation->id]);
    }

    public function bulkUpdate(Request $request, BulkUpdateReservationsAction $action): JsonResponse
    {
        $validated = $request->validate([
            'ids' => ['required', 'array'],
            'ids.*' => ['integer'],
            'data' => ['required', 'array'],
            'data.stage' => ['sometimes', 'string', 'max:100'],
            'data.status' => ['sometimes', 'string', 'max:100'],
        ]);

        $count = $action->handle(
            reservationIds: $validated['ids'],
            data: $validated['data'],
            user: $request->user(),
        );

        return response()->json(['updated' => $count]);
    }
}
