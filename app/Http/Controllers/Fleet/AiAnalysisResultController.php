<?php

declare(strict_types=1);

namespace App\Http\Controllers\Fleet;

use App\Http\Controllers\Controller;
use App\Models\Fleet\AiAnalysisResult;
use Inertia\Inertia;
use Inertia\Response;

final class AiAnalysisResultController extends Controller
{
    public function index(): Response
    {
        $this->authorize('viewAny', AiAnalysisResult::class);
        $results = AiAnalysisResult::query()->latest()
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('Fleet/AiAnalysisResults/Index', [
            'aiAnalysisResults' => $results,
            'analysisTypes' => array_map(fn (\App\Enums\Fleet\AiAnalysisType $c): array => ['value' => $c->value, 'name' => $c->name], \App\Enums\Fleet\AiAnalysisType::cases()),
            'statuses' => array_map(fn (\App\Enums\Fleet\AiAnalysisStatus $c): array => ['value' => $c->value, 'name' => $c->name], \App\Enums\Fleet\AiAnalysisStatus::cases()),
            'summary' => Inertia::defer(fn (): array => $this->computeSummary()),
        ]);
    }

    public function show(AiAnalysisResult $ai_analysis_result): Response
    {
        $this->authorize('view', $ai_analysis_result);
        $ai_analysis_result->load('reviewedBy');

        return Inertia::render('Fleet/AiAnalysisResults/Show', [
            'aiAnalysisResult' => $ai_analysis_result,
            'analysisTypes' => array_map(fn (\App\Enums\Fleet\AiAnalysisType $c): array => ['value' => $c->value, 'name' => $c->name], \App\Enums\Fleet\AiAnalysisType::cases()),
            'statuses' => array_map(fn (\App\Enums\Fleet\AiAnalysisStatus $c): array => ['value' => $c->value, 'name' => $c->name], \App\Enums\Fleet\AiAnalysisStatus::cases()),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function computeSummary(): array
    {
        $query = AiAnalysisResult::query();

        $total = $query->count();
        $highPriority = (clone $query)->whereIn('priority', ['high', 'critical'])->count();
        $mediumPriority = (clone $query)->where('priority', 'medium')->count();
        $avgConfidence = $total > 0
            ? round((float) (clone $query)->avg('confidence_score') * 100)
            : 0;

        return [
            'totalResults' => $total,
            'highPriority' => $highPriority,
            'mediumPriority' => $mediumPriority,
            'avgConfidence' => $avgConfidence,
        ];
    }
}
