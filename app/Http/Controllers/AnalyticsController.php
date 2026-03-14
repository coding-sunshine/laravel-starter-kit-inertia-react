<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\NlAnalyticsQueryAction;
use App\Models\Contact;
use App\Models\PropertyReservation;
use App\Models\Sale;
use App\Models\Task;
use App\Services\TenantContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final class AnalyticsController extends Controller
{
    public function index(): Response
    {
        $organizationId = TenantContext::id();

        $contactsByStage = Contact::query()
            ->when($organizationId, fn ($q) => $q->where('organization_id', $organizationId))
            ->selectRaw('stage, count(*) as count')
            ->whereNotNull('stage')
            ->groupBy('stage')
            ->pluck('count', 'stage')
            ->toArray();

        $salesByStatus = Sale::query()
            ->when($organizationId, fn ($q) => $q->where('organization_id', $organizationId))
            ->selectRaw('status, count(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();

        $reservationsByStage = PropertyReservation::query()
            ->when($organizationId, fn ($q) => $q->where('organization_id', $organizationId))
            ->selectRaw('stage, count(*) as count')
            ->whereNotNull('stage')
            ->groupBy('stage')
            ->pluck('count', 'stage')
            ->toArray();

        $tasksByType = Task::query()
            ->when($organizationId, fn ($q) => $q->where('organization_id', $organizationId))
            ->selectRaw('type, count(*) as count')
            ->groupBy('type')
            ->pluck('count', 'type')
            ->toArray();

        $totalContacts = Contact::query()
            ->when($organizationId, fn ($q) => $q->where('organization_id', $organizationId))
            ->count();

        $activeSales = Sale::query()
            ->when($organizationId, fn ($q) => $q->where('organization_id', $organizationId))
            ->whereNotIn('status', ['settled', 'cancelled'])
            ->count();

        $openTasks = Task::query()
            ->when($organizationId, fn ($q) => $q->where('organization_id', $organizationId))
            ->where('is_completed', false)
            ->count();

        $settledThisMonth = Sale::query()
            ->when($organizationId, fn ($q) => $q->where('organization_id', $organizationId))
            ->where('status', 'settled')
            ->whereBetween('settled_at', [now()->startOfMonth(), now()->endOfMonth()])
            ->count();

        return Inertia::render('analytics/index', [
            'stats' => [
                'totalContacts' => $totalContacts,
                'activeSales' => $activeSales,
                'openTasks' => $openTasks,
                'settledThisMonth' => $settledThisMonth,
            ],
            'charts' => [
                'contactsByStage' => $contactsByStage,
                'salesByStatus' => $salesByStatus,
                'reservationsByStage' => $reservationsByStage,
                'tasksByType' => $tasksByType,
            ],
        ]);
    }

    public function nlQuery(Request $request, NlAnalyticsQueryAction $action): JsonResponse
    {
        $validated = $request->validate([
            'query' => ['required', 'string', 'max:500'],
            'context' => ['nullable', 'string', 'max:100'],
        ]);

        $result = $action->handle(
            query: $validated['query'],
            context: $validated['context'] ?? 'general',
        );

        return response()->json($result);
    }
}
