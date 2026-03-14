<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\PushListingAction;
use App\DataTables\LotDataTable;
use App\Models\Lot;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final class LotsTableController extends Controller
{
    public function index(Request $request): Response
    {
        return Inertia::render('lots/index', LotDataTable::inertiaProps($request));
    }

    public function push(Request $request, Lot $lot, PushListingAction $action): JsonResponse
    {
        $validated = $request->validate([
            'channel' => ['required', 'string', 'in:php,wordpress'],
        ]);

        $action->handle($lot, $validated['channel'], $request->user());

        return response()->json(['success' => true]);
    }
}
