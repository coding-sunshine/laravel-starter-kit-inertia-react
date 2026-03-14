<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\GenerateDealForecastAction;
use App\Models\Sale;
use Illuminate\Http\JsonResponse;

final class DealForecastController extends Controller
{
    public function show(Sale $sale, GenerateDealForecastAction $action): JsonResponse
    {
        $forecast = $action->handle($sale);

        return response()->json([
            'sale_id' => $sale->id,
            'forecast' => $forecast,
        ]);
    }
}
