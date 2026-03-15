<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\FetchSuburbAiDataAction;
use App\Models\SuburbAiData;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Suburb AI data controller — fetch and display AI-generated suburb market data.
 */
final class SuburbAiDataController extends Controller
{
    public function index(): Response
    {
        $data = SuburbAiData::query()
            ->where('organization_id', tenant('id'))
            ->orderByDesc('fetched_at')
            ->paginate(20);

        return Inertia::render('suburb-ai/index', [
            'suburb_data' => $data,
        ]);
    }

    public function fetch(Request $request, FetchSuburbAiDataAction $action): JsonResponse
    {
        $validated = $request->validate([
            'suburb' => ['required', 'string', 'max:100'],
            'state' => ['nullable', 'string', 'max:50'],
            'postcode' => ['nullable', 'string', 'max:10'],
            'force_refresh' => ['boolean'],
        ]);

        $result = $action->handle(
            suburbName: $validated['suburb'],
            state: $validated['state'] ?? null,
            postcode: $validated['postcode'] ?? null,
            organizationId: tenant('id'),
            forceRefresh: (bool) ($validated['force_refresh'] ?? false),
        );

        return response()->json([
            'success' => true,
            'data' => $result,
        ]);
    }
}
