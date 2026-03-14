<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\PushListingAction;
use App\DataTables\ProjectDataTable;
use App\Models\Project;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final class ProjectsTableController extends Controller
{
    public function index(Request $request): Response
    {
        return Inertia::render('projects/index', ProjectDataTable::inertiaProps($request));
    }

    public function push(Request $request, Project $project, PushListingAction $action): JsonResponse
    {
        $validated = $request->validate([
            'channel' => ['required', 'string', 'in:php,wordpress'],
        ]);

        $action->handle($project, $validated['channel'], $request->user());

        return response()->json(['success' => true]);
    }
}
