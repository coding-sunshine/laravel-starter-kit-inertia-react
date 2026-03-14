<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Sale;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final class PipelineController extends Controller
{
    public function index(Request $request): Response
    {
        $sales = Sale::query()
            ->select(['id', 'status', 'client_contact_id', 'lot_id', 'project_id', 'comms_in_total', 'comms_out_total', 'settled_at', 'created_at', 'status_updated_at'])
            ->orderByDesc('created_at')
            ->get();

        $grouped = $sales->groupBy('status')->map(fn ($group) => $group->values())->toArray();

        $statuses = $sales->pluck('status')->unique()->values()->toArray();

        return Inertia::render('pipeline/index', [
            'grouped' => $grouped,
            'statuses' => $statuses,
            'total' => $sales->count(),
        ]);
    }
}
