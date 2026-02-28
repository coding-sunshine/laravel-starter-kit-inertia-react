<?php

declare(strict_types=1);

namespace App\Http\Controllers\Fleet;

use App\Http\Controllers\Controller;
use App\Models\Fleet\ApiLog;
use App\Models\Fleet\ApiIntegration;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final class ApiLogController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', ApiLog::class);
        $logs = ApiLog::query()
            ->with('apiIntegration')
            ->when($request->input('integration_id'), fn ($q, $v) => $q->where('integration_id', $v))
            ->when($request->input('date_from'), fn ($q, $v) => $q->whereDate('created_at', '>=', $v))
            ->when($request->input('date_to'), fn ($q, $v) => $q->whereDate('created_at', '<=', $v))
            ->orderByDesc('created_at')
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('Fleet/ApiLogs/Index', [
            'apiLogs' => $logs,
            'filters' => $request->only(['integration_id', 'date_from', 'date_to']),
            'apiIntegrations' => ApiIntegration::query()->orderBy('integration_name')->get(['id', 'integration_name']),
        ]);
    }

    public function show(ApiLog $api_log): Response
    {
        $this->authorize('view', $api_log);
        $api_log->load('apiIntegration', 'user');
        return Inertia::render('Fleet/ApiLogs/Show', ['apiLog' => $api_log]);
    }
}
