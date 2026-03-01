<?php

declare(strict_types=1);

namespace App\Http\Controllers\Fleet;

use App\Http\Controllers\Controller;
use App\Models\Fleet\AiAnalysisResult;
use App\Services\Ai\FleetElectrificationService;
use App\Services\TenantContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final class FleetElectrificationController extends Controller
{
    public function index(): Response
    {
        $this->authorize('viewAny', AiAnalysisResult::class);
        $latest = AiAnalysisResult::query()
            ->where('analysis_type', 'electrification_planning')
            ->where('entity_type', 'organization')
            ->orderByDesc('created_at')
            ->first();

        return Inertia::render('Fleet/ElectrificationPlan/Index', [
            'latestPlan' => $latest,
            'generateUrl' => route('fleet.electrification-plan.generate'),
        ]);
    }

    public function generate(Request $request): JsonResponse
    {
        $this->authorize('create', AiAnalysisResult::class);
        $organizationId = TenantContext::id();
        if ($organizationId === null) {
            return response()->json(['message' => 'No organization context.'], 422);
        }

        $service = app(FleetElectrificationService::class);
        $result = $service->generate($organizationId);
        if ($result === null) {
            return response()->json(['message' => 'Failed to generate electrification plan.'], 422);
        }

        $score = $result['readiness_score'] ?? 0;
        $replacementCount = count($result['replacement_order'] ?? []);
        $primaryFinding = sprintf(
            'Readiness: %d%%. %d vehicle(s) in replacement order. TCO savings: %s.',
            (int) $score,
            $replacementCount,
            isset($result['tco_summary']['savings']) ? number_format((float) $result['tco_summary']['savings'], 0) : 'N/A'
        );

        AiAnalysisResult::create([
            'organization_id' => $organizationId,
            'analysis_type' => 'electrification_planning',
            'entity_type' => 'organization',
            'entity_id' => $organizationId,
            'model_name' => 'fleet_electrification',
            'model_version' => null,
            'confidence_score' => 0.85,
            'risk_score' => 0,
            'priority' => 'medium',
            'primary_finding' => $primaryFinding,
            'detailed_analysis' => $result,
            'recommendations' => $result['charging_recommendations'] ?? null,
            'action_items' => $result['milestones'] ?? null,
            'business_impact' => $result['tco_summary'] ?? null,
            'status' => 'pending',
            'created_by' => $request->user()?->id,
            'updated_by' => $request->user()?->id,
        ]);

        return response()->json([
            'message' => 'Electrification plan generated.',
            'result' => $result,
        ]);
    }
}
