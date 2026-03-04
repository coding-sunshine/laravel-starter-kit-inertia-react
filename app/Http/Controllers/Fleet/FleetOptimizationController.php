<?php

declare(strict_types=1);

namespace App\Http\Controllers\Fleet;

use App\Http\Controllers\Controller;
use App\Models\Fleet\AiAnalysisResult;
use App\Services\Ai\FleetOptimizationService;
use App\Services\TenantContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final class FleetOptimizationController extends Controller
{
    public function index(): Response
    {
        $this->authorize('viewAny', AiAnalysisResult::class);
        $latest = AiAnalysisResult::query()
            ->where('analysis_type', 'cost_optimization')
            ->where('entity_type', 'organization')->latest()
            ->first();

        return Inertia::render('Fleet/FleetOptimization/Index', [
            'latestAnalysis' => $latest,
            'analyzeUrl' => route('fleet.fleet-optimization.analyze'),
        ]);
    }

    public function analyze(Request $request): JsonResponse
    {
        $this->authorize('create', AiAnalysisResult::class);
        $organizationId = TenantContext::id();
        if ($organizationId === null) {
            return response()->json(['message' => 'No organization context.'], 422);
        }

        $service = resolve(FleetOptimizationService::class);
        $result = $service->analyze($organizationId);
        if ($result === null) {
            return response()->json(['message' => 'Failed to run fleet optimization analysis.'], 422);
        }

        $primaryFinding = 'Fleet optimization: right-sizing, replacement timing, and fleet mix recommendations with '.count($result['what_if_scenarios'] ?? []).' what-if scenario(s).';

        AiAnalysisResult::query()->create([
            'organization_id' => $organizationId,
            'analysis_type' => 'cost_optimization',
            'entity_type' => 'organization',
            'entity_id' => $organizationId,
            'model_name' => 'fleet_optimization',
            'model_version' => null,
            'confidence_score' => 0.85,
            'risk_score' => 0,
            'priority' => 'medium',
            'primary_finding' => $primaryFinding,
            'detailed_analysis' => $result,
            'recommendations' => null,
            'action_items' => $result['what_if_scenarios'] ?? null,
            'business_impact' => null,
            'status' => 'pending',
            'created_by' => $request->user()?->id,
            'updated_by' => $request->user()?->id,
        ]);

        return response()->json([
            'message' => 'Fleet optimization analysis complete.',
            'result' => $result,
        ]);
    }
}
