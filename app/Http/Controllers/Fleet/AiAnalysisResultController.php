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
        $results = AiAnalysisResult::query()
            ->orderByDesc('created_at')
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('Fleet/AiAnalysisResults/Index', [
            'aiAnalysisResults' => $results,
            'analysisTypes' => array_map(fn ($c) => ['value' => $c->value, 'name' => $c->name], \App\Enums\Fleet\AiAnalysisType::cases()),
            'statuses' => array_map(fn ($c) => ['value' => $c->value, 'name' => $c->name], \App\Enums\Fleet\AiAnalysisStatus::cases()),
        ]);
    }

    public function show(AiAnalysisResult $ai_analysis_result): Response
    {
        $this->authorize('view', $ai_analysis_result);
        $ai_analysis_result->load('reviewedBy');

        return Inertia::render('Fleet/AiAnalysisResults/Show', [
            'aiAnalysisResult' => $ai_analysis_result,
            'analysisTypes' => array_map(fn ($c) => ['value' => $c->value, 'name' => $c->name], \App\Enums\Fleet\AiAnalysisType::cases()),
            'statuses' => array_map(fn ($c) => ['value' => $c->value, 'name' => $c->name], \App\Enums\Fleet\AiAnalysisStatus::cases()),
        ]);
    }
}
