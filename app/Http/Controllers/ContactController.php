<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\BulkUpdateContactsAction;
use App\DataTables\ContactDataTable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final class ContactController extends Controller
{
    public function index(Request $request): Response
    {
        return Inertia::render('contacts/index', ContactDataTable::inertiaProps($request));
    }

    public function bulkUpdate(Request $request, BulkUpdateContactsAction $action): JsonResponse
    {
        $validated = $request->validate([
            'ids' => ['required', 'array'],
            'ids.*' => ['integer'],
            'data' => ['required', 'array'],
            'data.stage' => ['sometimes', 'string', 'max:100'],
            'data.lead_score' => ['sometimes', 'integer', 'min:0', 'max:100'],
        ]);

        $count = $action->handle(
            contactIds: $validated['ids'],
            data: $validated['data'],
            user: $request->user(),
        );

        return response()->json(['updated' => $count]);
    }
}
