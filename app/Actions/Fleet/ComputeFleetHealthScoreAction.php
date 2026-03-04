<?php

declare(strict_types=1);

namespace App\Actions\Fleet;

use App\Models\Fleet\Alert;
use App\Models\Fleet\ComplianceItem;
use App\Models\Fleet\Defect;
use App\Models\Fleet\Vehicle;
use App\Models\Fleet\WorkOrder;
use App\Services\TenantContext;

/**
 * Compute a 0–100 fleet health score from compliance %, open alerts, defects, work orders overdue.
 */
final readonly class ComputeFleetHealthScoreAction
{
    /**
     * @return array{score: int, breakdown: array{compliance_pct: float, compliance_label: string, open_alerts: int, open_defects: int, overdue_work_orders: int, vehicles: int}}
     */
    public function handle(?int $organizationId = null): array
    {
        $organizationId ??= TenantContext::id();
        if ($organizationId === null) {
            return [
                'score' => 0,
                'breakdown' => [
                    'compliance_pct' => 0.0,
                    'compliance_label' => 'No data',
                    'open_alerts' => 0,
                    'open_defects' => 0,
                    'overdue_work_orders' => 0,
                    'vehicles' => 0,
                ],
            ];
        }

        $vehicles = Vehicle::query()->count();
        $totalCompliance = ComplianceItem::query()->count();
        $validCompliance = $totalCompliance > 0
            ? ComplianceItem::query()->whereIn('status', ['valid', 'expiring_soon'])->count()
            : 0;
        $compliancePct = $totalCompliance > 0 ? round(100.0 * $validCompliance / $totalCompliance, 1) : 100.0;
        $openAlerts = Alert::query()->where('status', 'active')->count();
        $openDefects = Defect::query()->whereIn('status', ['open', 'pending', 'in_progress'])->count();
        $overdueWorkOrders = WorkOrder::query()->whereNotNull('due_date')
            ->where('due_date', '<', now()->toDateString())
            ->whereNotIn('status', ['completed', 'cancelled'])
            ->count();

        $score = $this->computeScore($compliancePct, $openAlerts, $openDefects, $overdueWorkOrders);
        $complianceLabel = $totalCompliance === 0 ? 'No items' : "{$validCompliance}/{$totalCompliance} valid";

        return [
            'score' => min(100, max(0, $score)),
            'breakdown' => [
                'compliance_pct' => $compliancePct,
                'compliance_label' => $complianceLabel,
                'open_alerts' => $openAlerts,
                'open_defects' => $openDefects,
                'overdue_work_orders' => $overdueWorkOrders,
                'vehicles' => $vehicles,
            ],
        ];
    }

    private function computeScore(float $compliancePct, int $openAlerts, int $openDefects, int $overdueWorkOrders): int
    {
        $score = 100.0;
        $score -= (100.0 - $compliancePct) * 0.4;
        $score -= min(20, $openAlerts * 2);
        $score -= min(15, $openDefects * 3);
        $score -= min(25, $overdueWorkOrders * 5);

        return (int) round(max(0, $score));
    }
}
