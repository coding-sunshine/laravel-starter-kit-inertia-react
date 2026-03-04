<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Actions\GetDashboardMetrics;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

final class DashboardController extends Controller
{
    public function __construct(
        private readonly GetDashboardMetrics $getDashboardMetrics
    ) {}

    public function metrics(): JsonResponse
    {
        $metrics = $this->getDashboardMetrics->handle();

        return response()->json($metrics);
    }

    public function pipeline(): JsonResponse
    {
        $metrics = $this->getDashboardMetrics->handle();

        return response()->json($metrics['charts']['pipeline']);
    }

    public function revenue(): JsonResponse
    {
        $metrics = $this->getDashboardMetrics->handle();

        return response()->json($metrics['charts']['revenue_trends']);
    }

    public function distribution(): JsonResponse
    {
        $metrics = $this->getDashboardMetrics->handle();

        return response()->json($metrics['charts']['project_distribution']);
    }
}
