<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\BulkUpdateContactsAction;
use App\Actions\GenerateAiSummaryAction;
use App\Actions\UpdateContactStageAction;
use App\DataTables\ContactDataTable;
use App\Models\Contact;
use App\Services\AiSearchService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Throwable;

final class ContactController extends Controller
{
    public function index(Request $request): Response
    {
        return Inertia::render('contacts/index', ContactDataTable::inertiaProps($request));
    }

    public function show(int $contact): Response
    {
        $contact = Contact::query()->findOrFail($contact);

        $contact->load([
            'emails',
            'phones',
            'company',
            'source',
            'assignedUser:id,name,email',
            'tasks' => fn ($q) => $q->orderByDesc('created_at')->limit(10),
            'callLogs' => fn ($q) => $q->orderByDesc('called_at')->limit(10),
            'strategyTags',
            'aiSummaries' => fn ($q) => $q->latest()->limit(1),
            'propertySearches' => fn ($q) => $q->latest()->limit(5),
        ]);

        $activities = $contact->activities()
            ->with('causer:id,name')
            ->latest()
            ->limit(20)
            ->get()
            ->map(fn ($activity) => [
                'id' => $activity->id,
                'type' => $activity->event,
                'description' => $activity->description,
                'causer_name' => $activity->causer?->name,
                'properties' => $activity->properties?->toArray(),
                'created_at' => $activity->created_at?->toISOString(),
            ]);

        $stages = ['new_lead', 'contacted', 'qualified', 'nurturing', 'hot', 'client'];
        $currentStageIndex = array_search($contact->stage, $stages, true);

        return Inertia::render('contacts/show', [
            'contact' => [
                'id' => $contact->id,
                'first_name' => $contact->first_name,
                'last_name' => $contact->last_name,
                'job_title' => $contact->job_title,
                'type' => $contact->type,
                'stage' => $contact->stage,
                'contact_origin' => $contact->contact_origin,
                'company_name' => $contact->company_name,
                'lead_score' => $contact->lead_score,
                'last_contacted_at' => $contact->last_contacted_at?->toISOString(),
                'next_followup_at' => $contact->next_followup_at?->toISOString(),
                'last_followup_at' => $contact->last_followup_at?->toISOString(),
                'created_at' => $contact->created_at?->toISOString(),
                'extra_attributes' => $contact->extra_attributes,
                'emails' => $contact->emails->map(fn ($e) => [
                    'id' => $e->id,
                    'email' => $e->value,
                    'is_primary' => $e->is_primary,
                ]),
                'phones' => $contact->phones->map(fn ($p) => [
                    'id' => $p->id,
                    'phone' => $p->value,
                    'is_primary' => $p->is_primary,
                ]),
                'company' => $contact->company ? [
                    'id' => $contact->company->id,
                    'name' => $contact->company->name,
                ] : null,
                'source' => $contact->source?->name,
                'assigned_user' => $contact->assignedUser ? [
                    'id' => $contact->assignedUser->id,
                    'name' => $contact->assignedUser->name,
                ] : null,
                'strategy_tags' => $contact->strategyTags->map(fn ($t) => [
                    'id' => $t->id,
                    'name' => $t->name,
                ]),
                'ai_summary' => $contact->aiSummaries->first()?->content,
                'tasks' => $contact->tasks->map(fn ($t) => [
                    'id' => $t->id,
                    'title' => $t->title,
                    'status' => $t->status,
                    'due_at' => $t->due_at?->toISOString(),
                    'priority' => $t->priority,
                ]),
                'call_logs' => $contact->callLogs->map(fn ($c) => [
                    'id' => $c->id,
                    'direction' => $c->direction,
                    'duration_seconds' => $c->duration_seconds,
                    'outcome' => $c->outcome,
                    'called_at' => $c->called_at?->toISOString(),
                ]),
                'property_searches' => $contact->propertySearches->map(fn ($s) => [
                    'id' => $s->id,
                    'search_criteria' => $s->search_criteria,
                    'created_at' => $s->created_at?->toISOString(),
                ]),
            ],
            'activities' => $activities,
            'currentStageIndex' => $currentStageIndex !== false ? $currentStageIndex : 0,
            'ai_summary' => Inertia::defer(fn () => $this->getOrGenerateSummary($contact)),
            'similar_contacts' => Inertia::defer(fn () => $this->getSimilarContacts($contact)),
        ]);
    }

    public function update(Request $request, int $contact): JsonResponse
    {
        $contact = Contact::query()->findOrFail($contact);

        $validated = $request->validate([
            'first_name' => ['sometimes', 'string', 'max:255'],
            'last_name' => ['sometimes', 'nullable', 'string', 'max:255'],
            'job_title' => ['sometimes', 'nullable', 'string', 'max:255'],
            'company_name' => ['sometimes', 'nullable', 'string', 'max:255'],
            'stage' => ['sometimes', 'string', 'max:100'],
        ]);

        if (isset($validated['stage'])) {
            app(UpdateContactStageAction::class)->handle($contact, $validated['stage']);
            unset($validated['stage']);
        }

        if ($validated !== []) {
            $contact->update($validated);
        }

        return response()->json(['success' => true, 'id' => $contact->id]);
    }

    public function refreshSummary(int $contact, GenerateAiSummaryAction $action): JsonResponse
    {
        $contact = Contact::query()->findOrFail($contact);
        $context = "{$contact->first_name} {$contact->last_name} | Type: {$contact->type} | Stage: {$contact->stage}";
        $summary = $action->handle($contact, $context);

        return response()->json(['content' => $summary->content]);
    }

    public function quickEdit(Request $request, int $contact): JsonResponse
    {
        $contact = Contact::query()->findOrFail($contact);

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

    /**
     * @return array{content: string}|null
     */
    private function getOrGenerateSummary(Contact $contact): ?array
    {
        $summary = $contact->aiSummaries()->latest()->first();

        if ($summary !== null) {
            return ['content' => $summary->content];
        }

        try {
            $context = "{$contact->first_name} {$contact->last_name} | Type: {$contact->type} | Stage: {$contact->stage}";
            $generated = app(GenerateAiSummaryAction::class)->handle($contact, $context);

            return ['content' => $generated->content];
        } catch (Throwable) {
            return null;
        }
    }

    /**
     * @return array<int, array{id: int, name: string, type: string, stage: string|null}>
     */
    private function getSimilarContacts(Contact $contact): array
    {
        try {
            return app(AiSearchService::class)
                ->findSimilar($contact, 5)
                ->map(fn (Contact $c) => [
                    'id' => $c->id,
                    'name' => mb_trim("{$c->first_name} {$c->last_name}"),
                    'type' => $c->type,
                    'stage' => $c->stage,
                ])
                ->values()
                ->all();
        } catch (Throwable) {
            return [];
        }
    }
}
