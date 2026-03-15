<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\SendBuilderEmailAction;
use App\Models\BuilderEmailLog;
use App\Models\Contact;
use App\Models\Project;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Builder email controller — send templated emails to builders from the CRM.
 */
final class BuilderEmailController extends Controller
{
    public function index(): Response
    {
        $projects = Project::query()
            ->where('organization_id', tenant('id'))
            ->select('id', 'title', 'stage')
            ->orderBy('title')
            ->get();

        $recentLogs = BuilderEmailLog::query()
            ->where('organization_id', tenant('id'))
            ->with(['project:id,title', 'contact:id,first_name,last_name', 'sentByUser:id,name'])
            ->orderByDesc('created_at')
            ->limit(20)
            ->get();

        return Inertia::render('builder-email/index', [
            'projects' => $projects,
            'recent_logs' => $recentLogs,
            'template_types' => [
                ['value' => 'price_list', 'label' => 'Price List & Availability'],
                ['value' => 'more_info', 'label' => 'Request More Information'],
                ['value' => 'hold_request', 'label' => 'Hold Request'],
                ['value' => 'property_request', 'label' => 'Property Request'],
            ],
        ]);
    }

    public function send(Request $request, SendBuilderEmailAction $action): JsonResponse
    {
        $validated = $request->validate([
            'recipient_email' => ['required', 'email'],
            'recipient_name' => ['required', 'string', 'max:255'],
            'template_type' => ['required', 'string', 'in:price_list,more_info,hold_request,property_request'],
            'project_id' => ['nullable', 'exists:projects,id'],
            'contact_id' => ['nullable', 'exists:contacts,id'],
            'message' => ['nullable', 'string', 'max:2000'],
            'payload' => ['nullable', 'array'],
        ]);

        $project = isset($validated['project_id'])
            ? Project::find($validated['project_id'])
            : null;

        $contact = isset($validated['contact_id'])
            ? Contact::find($validated['contact_id'])
            : null;

        $log = $action->handle(
            sender: $request->user(),
            recipientEmail: $validated['recipient_email'],
            recipientName: $validated['recipient_name'],
            templateType: $validated['template_type'],
            project: $project,
            contact: $contact,
            message: $validated['message'] ?? '',
            payload: $validated['payload'] ?? [],
        );

        return response()->json([
            'success' => true,
            'log_id' => $log->id,
            'message' => 'Email sent successfully.',
        ]);
    }
}
