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

    public function show(Project $project): Response
    {
        $project->load([
            'developer',
            'projecttype',
            'lots' => fn ($q) => $q->orderBy('lot_number'),
            'projectUpdates' => fn ($q) => $q->latest()->limit(10),
            'flyers',
            'media',
        ]);

        $lotStats = [
            'total' => $project->lots->count(),
            'available' => $project->lots->where('status', 'available')->count(),
            'sold' => $project->lots->where('status', 'sold')->count(),
            'reserved' => $project->lots->where('status', 'reserved')->count(),
        ];

        $propertyBadges = collect([
            'is_smsf' => 'SMSF',
            'is_firb' => 'FIRB',
            'is_ndis' => 'NDIS',
            'is_cashflow_positive' => 'Cashflow+',
            'is_co_living' => 'Co-Living',
            'is_high_cap_growth' => 'High Growth',
            'is_rooming' => 'Rooming',
            'is_rent_to_sell' => 'Rent to Sell',
            'is_exclusive' => 'Exclusive',
        ])->filter(fn ($label, $key) => $project->$key)->values()->all();

        return Inertia::render('projects/show', [
            'project' => [
                'id' => $project->id,
                'slug' => $project->slug,
                'title' => $project->title,
                'stage' => $project->stage,
                'estate' => $project->estate,
                'suburb' => $project->suburb,
                'state' => $project->state,
                'postcode' => $project->postcode,
                'description' => $project->description,
                'description_summary' => $project->description_summary,
                'min_price' => $project->min_price,
                'max_price' => $project->max_price,
                'avg_price' => $project->avg_price,
                'min_rent' => $project->min_rent,
                'max_rent' => $project->max_rent,
                'rent_yield' => $project->rent_yield,
                'total_lots' => $project->total_lots,
                'bedrooms' => $project->bedrooms,
                'bathrooms' => $project->bathrooms,
                'garage' => $project->garage,
                'storeys' => $project->storeys,
                'build_time' => $project->build_time,
                'historical_growth' => $project->historical_growth,
                'is_hot_property' => $project->is_hot_property,
                'is_featured' => $project->is_featured,
                'is_archived' => $project->is_archived,
                'developer_name' => $project->developer?->name,
                'project_type' => $project->projecttype?->name,
                'property_badges' => $propertyBadges,
                'images' => rescue(fn () => $project->getMedia('photo')->map(fn ($m) => $m->getUrl())->all(), []),
                'created_at' => $project->created_at?->toISOString(),
            ],
            'lotStats' => $lotStats,
            'lots' => $project->lots->map(fn ($lot) => [
                'id' => $lot->id,
                'lot_number' => $lot->lot_number,
                'address' => $lot->address,
                'status' => $lot->status,
                'price' => $lot->price,
                'land_size' => $lot->land_size,
                'bedrooms' => $lot->bedrooms,
                'bathrooms' => $lot->bathrooms,
                'garage' => $lot->garage,
            ]),
            'updates' => $project->projectUpdates->map(fn ($u) => [
                'id' => $u->id,
                'title' => $u->title,
                'content' => $u->content,
                'created_at' => $u->created_at?->toISOString(),
            ]),
        ]);
    }

    public function featured(Request $request): Response
    {
        $request->merge(['filter' => array_merge($request->input('filter', []), ['is_featured' => '1'])]);

        return Inertia::render('projects/index', array_merge(
            ProjectDataTable::inertiaProps($request),
            ['pageTitle' => 'Featured Projects']
        ));
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
