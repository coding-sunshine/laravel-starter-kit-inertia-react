<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Contact;
use App\Models\PropertyReservation;
use App\Models\Sale;
use App\Models\Task;
use App\Services\PrismService;
use App\Services\TenantContext;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Inertia\Inertia;
use Inertia\Response;

final class DashboardController extends Controller
{
    /**
     * Dashboard with role-aware KPIs (contacts, tasks, reservations, sales).
     * When tenant context is set, includes aggregates and optional AI insight.
     */
    public function __invoke(Request $request): Response
    {
        $kpis = $this->kpis();
        $insight = $this->cachedInsight($kpis);
        $roleHint = $this->dashboardRoleHint($request->user());

        return Inertia::render('dashboard', [
            'kpis' => $kpis,
            'insight' => $insight,
            'dashboard_role' => $roleHint,
        ]);
    }

    /**
     * Role hint for dashboard (admin, sales_agent, bdm, member) for role-aware UI.
     */
    private function dashboardRoleHint(?\Illuminate\Contracts\Auth\Authenticatable $user): ?string
    {
        if ($user === null) {
            return null;
        }
        $roles = method_exists($user, 'getRoleNames') ? $user->getRoleNames()->all() : [];
        $roleLower = implode(' ', array_map(fn ($r) => mb_strtolower((string) $r), $roles));
        if ($user->can('access admin panel') ?? false) {
            return 'admin';
        }
        if (str_contains($roleLower, 'bdm')) {
            return 'bdm';
        }
        if (str_contains($roleLower, 'sales') || str_contains($roleLower, 'agent')) {
            return 'sales_agent';
        }

        return 'member';
    }

    /**
     * @return array<string, mixed>
     */
    private function kpis(): array
    {
        if (! TenantContext::check()) {
            return [
                'contacts_total' => 0,
                'contacts_by_stage' => [],
                'tasks_open' => 0,
                'tasks_overdue' => 0,
                'reservations_this_month' => 0,
                'sales_this_month' => 0,
                'sales_pipeline_value' => 0,
            ];
        }

        $now = now();
        $startOfMonth = $now->copy()->startOfMonth();
        $endOfMonth = $now->copy()->endOfMonth();

        $contactsByStage = Contact::query()
            ->selectRaw('stage, count(*) as count')
            ->whereNotNull('stage')
            ->where('stage', '!=', '')
            ->groupBy('stage')
            ->orderByDesc('count')
            ->pluck('count', 'stage')
            ->all();

        $tasksOpen = Task::query()->whereNull('completed_at')->count();
        $tasksOverdue = Task::query()
            ->whereNull('completed_at')
            ->where('due_at', '<', $now)
            ->count();

        $reservationsThisMonth = PropertyReservation::query()
            ->whereBetween('created_at', [$startOfMonth, $endOfMonth])
            ->count();

        $salesThisMonth = Sale::query()
            ->whereBetween('created_at', [$startOfMonth, $endOfMonth])
            ->count();

        $pipelineValue = (float) Sale::query()
            ->selectRaw('COALESCE(SUM(COALESCE(comms_in_total, 0) + COALESCE(comms_out_total, 0)), 0) as total')
            ->value('total');

        return [
            'contacts_total' => Contact::query()->count(),
            'contacts_by_stage' => $contactsByStage,
            'tasks_open' => $tasksOpen,
            'tasks_overdue' => $tasksOverdue,
            'reservations_this_month' => $reservationsThisMonth,
            'sales_this_month' => $salesThisMonth,
            'sales_pipeline_value' => round($pipelineValue, 2),
        ];
    }

    private function cachedInsight(array $kpis): ?string
    {
        if (! TenantContext::check()) {
            return null;
        }

        $cacheKey = 'dashboard_insight_'.TenantContext::id();
        $ttl = (int) config('reporting.dashboard_insight_ttl', 600); // 10 min

        return Cache::remember($cacheKey, $ttl, function () use ($kpis): ?string {
            try {
                $prism = resolve(PrismService::class);
                if (! $prism->isAvailable()) {
                    return null;
                }
                $summary = json_encode([
                    'contacts_total' => $kpis['contacts_total'],
                    'tasks_open' => $kpis['tasks_open'],
                    'tasks_overdue' => $kpis['tasks_overdue'],
                    'reservations_this_month' => $kpis['reservations_this_month'],
                    'sales_this_month' => $kpis['sales_this_month'],
                ], JSON_THROW_ON_ERROR);
                $prompt = "In one short sentence (max 15 words), give a single actionable insight or recommendation for a CRM user based on these numbers. Be specific. Numbers: {$summary}. Reply with only the sentence.";
                $response = $prism->generate($prompt);

                return $response->text ? trim($response->text) : null;
            } catch (\Throwable) {
                return null;
            }
        });
    }
}
