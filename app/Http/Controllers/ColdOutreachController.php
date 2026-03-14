<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\GenerateColdOutreachAction;
use App\Models\ColdOutreachTemplate;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Cold outreach template management with AI copy generation.
 */
final class ColdOutreachController extends Controller
{
    public function __construct(private GenerateColdOutreachAction $generateAction)
    {
        //
    }

    public function index(): Response
    {
        $templates = ColdOutreachTemplate::query()
            ->where(fn ($q) => $q->whereNull('organization_id')
                ->orWhere('organization_id', request()->user()?->currentOrganization()?->id))
            ->latest()
            ->paginate(20);

        return Inertia::render('cold-outreach/index', [
            'templates' => $templates,
        ]);
    }

    /**
     * Generate AI copy for a new cold outreach template.
     */
    public function generate(Request $request): JsonResponse
    {
        $data = $request->validate([
            'channel' => ['required', 'string', 'in:email,sms'],
            'tone' => ['required', 'string', 'in:professional,friendly,urgent,casual'],
            'context' => ['required', 'array'],
            'context.first_name' => ['nullable', 'string'],
            'context.property_type' => ['nullable', 'string'],
            'context.suburb' => ['nullable', 'string'],
            'context.price_range' => ['nullable', 'string'],
            'context.name' => ['nullable', 'string'],
        ]);

        $organizationId = $request->user()?->currentOrganization()?->id ?? 1;

        $template = $this->generateAction->handle(
            channel: $data['channel'],
            tone: $data['tone'],
            context: $data['context'],
            organizationId: $organizationId,
        );

        return response()->json([
            'success' => true,
            'template' => [
                'id' => $template->id,
                'subject' => $template->subject,
                'body' => $template->body,
                'ctas' => $template->ctas,
            ],
        ]);
    }
}
