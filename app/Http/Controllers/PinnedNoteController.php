<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\CreatePinnedNoteAction;
use App\Models\PinnedNote;
use App\Models\PropertyReservation;
use App\Models\Sale;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class PinnedNoteController extends Controller
{
    public function indexForReservation(PropertyReservation $reservation): JsonResponse
    {
        $notes = $reservation->pinnedNotes()
            ->with('author')
            ->where('is_active', true)
            ->orderBy('order')
            ->get();

        return response()->json($notes);
    }

    public function indexForSale(Sale $sale): JsonResponse
    {
        $notes = $sale->pinnedNotes()
            ->with('author')
            ->where('is_active', true)
            ->orderBy('order')
            ->get();

        return response()->json($notes);
    }

    public function storeForReservation(Request $request, PropertyReservation $reservation, CreatePinnedNoteAction $action): JsonResponse
    {
        $validated = $request->validate([
            'content' => ['required', 'string', 'max:10000'],
            'role_visibility' => ['nullable', 'array'],
            'role_visibility.*' => ['string'],
        ]);

        $note = $action->handle($reservation, $validated['content'], $validated['role_visibility'] ?? []);

        return response()->json($note->load('author'), 201);
    }

    public function storeForSale(Request $request, Sale $sale, CreatePinnedNoteAction $action): JsonResponse
    {
        $validated = $request->validate([
            'content' => ['required', 'string', 'max:10000'],
            'role_visibility' => ['nullable', 'array'],
            'role_visibility.*' => ['string'],
        ]);

        $note = $action->handle($sale, $validated['content'], $validated['role_visibility'] ?? []);

        return response()->json($note->load('author'), 201);
    }

    public function destroy(PinnedNote $pinnedNote): JsonResponse
    {
        $pinnedNote->delete();

        return response()->json(['success' => true]);
    }
}
