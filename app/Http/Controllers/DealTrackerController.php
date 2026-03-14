<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\DataTables\PropertyReservationDataTable;
use App\Models\PropertyReservation;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final class DealTrackerController extends Controller
{
    public function index(Request $request): Response
    {
        $stages = ['enquiry', 'qualified', 'reservation', 'contract', 'unconditional', 'settled'];

        $reservationsByStage = PropertyReservation::query()
            ->with(['primaryContact', 'lot', 'project'])
            ->whereNull('deleted_at')
            ->get()
            ->groupBy(fn (PropertyReservation $r) => $r->stage)
            ->map(fn ($items) => $items->values());

        $kanbanColumns = collect($stages)->map(fn (string $stage) => [
            'stage' => $stage,
            'label' => ucfirst($stage),
            'reservations' => $reservationsByStage->get($stage, collect())->map(fn (PropertyReservation $r) => [
                'id' => $r->id,
                'stage' => $r->stage,
                'purchase_price' => $r->purchase_price,
                'primary_contact_id' => $r->primary_contact_id,
                'lot_id' => $r->lot_id,
                'project_id' => $r->project_id,
                'deposit_status' => $r->deposit_status,
                'days_in_stage' => $r->updated_at ? (int) $r->updated_at->diffInDays(now()) : 0,
                'created_at' => $r->created_at?->format('Y-m-d'),
            ])->values(),
        ])->values();

        return Inertia::render('deal-tracker/index', array_merge(
            PropertyReservationDataTable::inertiaProps($request),
            [
                'kanbanColumns' => $kanbanColumns,
            ]
        ));
    }

    public function stageUpdate(Request $request, PropertyReservation $reservation): JsonResponse
    {
        $validated = $request->validate([
            'stage' => ['required', 'string', 'in:enquiry,qualified,reservation,contract,unconditional,settled'],
        ]);

        $reservation->update(['stage' => $validated['stage']]);

        return response()->json(['success' => true, 'stage' => $reservation->stage]);
    }
}
