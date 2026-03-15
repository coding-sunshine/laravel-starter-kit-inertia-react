<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\EnrollInNurtureSequenceAction;
use App\Models\Contact;
use App\Models\NurtureSequence;
use App\Services\TenantContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Manage nurture sequences and enroll contacts.
 */
final class NurtureSequenceController extends Controller
{
    public function __construct(private EnrollInNurtureSequenceAction $enrollAction)
    {
        //
    }

    public function index(): Response
    {
        $sequences = NurtureSequence::query()
            ->withCount(['enrollments', 'steps'])
            ->where(fn ($q) => $q->whereNull('organization_id')
                ->orWhere('organization_id', TenantContext::id()))
            ->latest()
            ->paginate(20);

        return Inertia::render('nurture-sequences/index', [
            'sequences' => $sequences,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'trigger_stage' => ['nullable', 'string', 'in:new,warm,hot,qualified,cold,nurture'],
            'is_active' => ['boolean'],
            'steps' => ['nullable', 'array'],
            'steps.*.channel' => ['required', 'string', 'in:email,sms,task'],
            'steps.*.subject' => ['nullable', 'string'],
            'steps.*.template_body' => ['required', 'string'],
            'steps.*.delay_days' => ['required', 'integer', 'min:0', 'max:365'],
            'steps.*.step_order' => ['required', 'integer', 'min:1'],
        ]);

        $sequence = NurtureSequence::query()->create([
            'organization_id' => TenantContext::id(),
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'trigger_stage' => $data['trigger_stage'] ?? null,
            'is_active' => $data['is_active'] ?? true,
        ]);

        foreach ($data['steps'] ?? [] as $stepData) {
            $sequence->steps()->create($stepData);
        }

        return redirect()->route('nurture-sequences.index')->with('success', 'Sequence created.');
    }

    public function enroll(Request $request, Contact $contact): JsonResponse
    {
        $data = $request->validate([
            'sequence_id' => ['required', 'integer', 'exists:nurture_sequences,id'],
        ]);

        $sequence = NurtureSequence::query()->findOrFail($data['sequence_id']);
        $enrollment = $this->enrollAction->handle($contact, $sequence);

        return response()->json(['success' => true, 'enrollment_id' => $enrollment->id]);
    }
}
