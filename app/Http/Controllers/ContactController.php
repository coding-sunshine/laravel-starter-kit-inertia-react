<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\BulkUpdateContactsAction;
use App\Actions\UpdateContactStageAction;
use App\DataTables\ContactDataTable;
use App\Models\Contact;
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

    public function quickEdit(Request $request, Contact $contact): JsonResponse
    {
        $validated = $request->validate([
            'stage' => ['sometimes', 'string', 'max:100'],
            'next_followup_at' => ['sometimes', 'nullable', 'date'],
            'lead_score' => ['sometimes', 'integer', 'min:0', 'max:100'],
        ]);

        if (isset($validated['stage'])) {
            app(UpdateContactStageAction::class)->handle($contact, $validated['stage']);
        }

        if (array_key_exists('next_followup_at', $validated)) {
            $contact->update(['next_followup_at' => $validated['next_followup_at']]);
        }

        if (isset($validated['lead_score'])) {
            $contact->update(['lead_score' => $validated['lead_score']]);
        }

        return response()->json(['success' => true, 'id' => $contact->id]);
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
