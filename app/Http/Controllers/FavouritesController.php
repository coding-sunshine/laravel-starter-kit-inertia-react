<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Project;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final class FavouritesController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();
        $projects = $user->favouriteProjects()
            ->with('developer')
            ->orderByPivot('created_at', 'desc')
            ->get()
            ->map(fn (Project $project) => [
                'id' => $project->id,
                'slug' => $project->slug,
                'title' => $project->title,
                'stage' => $project->stage,
                'suburb' => $project->suburb,
                'state' => $project->state,
                'developer_name' => $project->developer?->name,
                'min_price' => $project->min_price,
                'max_price' => $project->max_price,
                'total_lots' => $project->total_lots,
                'is_hot_property' => $project->is_hot_property,
                'is_featured' => $project->is_featured,
            ]);

        return Inertia::render('favourites/index', [
            'projects' => $projects,
        ]);
    }

    public function toggle(Request $request, Project $project): JsonResponse
    {
        $user = $request->user();
        $result = $user->favouriteProjects()->toggle($project->id);
        $isFavourited = count($result['attached']) > 0;

        return response()->json([
            'favourited' => $isFavourited,
        ]);
    }
}
