<?php

declare(strict_types=1);

namespace App\Http\Controllers\Fleet;

use App\Http\Controllers\Controller;
use App\Models\Fleet\AiJobRun;
use Inertia\Inertia;
use Inertia\Response;

final class AiJobRunController extends Controller
{
    public function index(): Response
    {
        $this->authorize('viewAny', AiJobRun::class);
        $runs = AiJobRun::query()
            ->orderByDesc('created_at')
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('Fleet/AiJobRuns/Index', [
            'aiJobRuns' => $runs,
            'statuses' => array_map(fn ($c) => ['value' => $c->value, 'name' => $c->name], \App\Enums\Fleet\AiJobRunStatus::cases()),
        ]);
    }

    public function show(AiJobRun $ai_job_run): Response
    {
        $this->authorize('view', $ai_job_run);

        return Inertia::render('Fleet/AiJobRuns/Show', [
            'aiJobRun' => $ai_job_run,
            'statuses' => array_map(fn ($c) => ['value' => $c->value, 'name' => $c->name], \App\Enums\Fleet\AiJobRunStatus::cases()),
        ]);
    }
}
