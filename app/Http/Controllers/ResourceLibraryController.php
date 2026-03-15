<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

final class ResourceLibraryController extends Controller
{
    public function index(Request $request): Response
    {
        $resources = DB::table('crm_resources')
            ->leftJoin('resource_categories', 'crm_resources.resource_category_id', '=', 'resource_categories.id')
            ->select([
                'crm_resources.id',
                'crm_resources.title',
                'resource_categories.name as category',
                'crm_resources.description',
                'crm_resources.url',
                'crm_resources.type',
                'crm_resources.created_at',
            ])
            ->orderBy('resource_categories.name')
            ->orderBy('crm_resources.title')
            ->get()
            ->map(fn ($r) => [
                'id' => $r->id,
                'title' => $r->title,
                'category' => $r->category ?? 'General',
                'description' => $r->description,
                'url' => $r->url,
                'type' => $r->type,
                'created_at' => $r->created_at,
            ])
            ->groupBy('category');

        return Inertia::render('resources/index', [
            'resourcesByCategory' => $resources,
        ]);
    }
}
