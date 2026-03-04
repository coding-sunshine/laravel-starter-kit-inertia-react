<?php

declare(strict_types=1);

namespace App\Http\Controllers\Fleet;

use App\Actions\Fleet\InterpretFleetFiltersAction;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class FleetFilterController extends Controller
{
    /**
     * Interpret natural-language query into suggested list filters.
     * POST /fleet/filters/interpret with list_type and natural_language_query.
     */
    public function interpret(Request $request, InterpretFleetFiltersAction $action): JsonResponse
    {
        $valid = $request->validate([
            'list_type' => ['required', 'string', 'in:vehicles,drivers,work_orders'],
            'natural_language_query' => ['required', 'string', 'max:500'],
        ]);

        $result = $action->handle(
            $valid['list_type'],
            $valid['natural_language_query'],
        );

        if ($result === null) {
            return response()->json(['suggested' => null, 'error' => 'AI unavailable'], 503);
        }

        return response()->json(['suggested' => $result]);
    }
}
