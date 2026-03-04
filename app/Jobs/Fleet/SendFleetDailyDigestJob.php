<?php

declare(strict_types=1);

namespace App\Jobs\Fleet;

use App\Actions\Fleet\GetFleetDashboardSummaryAction;
use App\Events\Fleet\FleetDailyDigestReady;
use App\Models\Fleet\Alert;
use App\Models\Fleet\ComplianceItem;
use App\Models\Fleet\Driver;
use App\Models\Fleet\Vehicle;
use App\Models\Fleet\WorkOrder;
use App\Models\Organization;
use App\Services\TenantContext;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Throwable;

final class SendFleetDailyDigestJob implements ShouldQueue
{
    use Queueable;

    public function handle(GetFleetDashboardSummaryAction $getSummary): void
    {
        $organizationIds = Organization::query()
            ->whereIn('id', Vehicle::query()->distinct()->pluck('organization_id'))
            ->pluck('id');

        foreach ($organizationIds as $organizationId) {
            try {
                $this->sendDigestForOrganization($organizationId, $getSummary);
            } catch (Throwable $e) {
                Log::error('Fleet daily digest failed for organization', [
                    'organization_id' => $organizationId,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    private function sendDigestForOrganization(int $organizationId, GetFleetDashboardSummaryAction $getSummary): void
    {
        TenantContext::set($organizationId);
        $organization = Organization::query()->find($organizationId);
        if ($organization === null) {
            return;
        }

        $activeAlertsCount = Alert::query()->where('status', 'active')->count();
        $expiringComplianceCount = ComplianceItem::query()->whereIn('status', ['valid', 'expiring_soon'])
            ->where('expiry_date', '<=', now()->addDays(30))
            ->count();
        $openWorkOrdersCount = WorkOrder::query()->whereIn('status', ['pending', 'in_progress'])->count();

        $counts = [
            'vehicles' => Vehicle::query()->count(),
            'drivers' => Driver::query()->count(),
            'work_orders' => WorkOrder::query()->count(),
            'alerts_open' => $activeAlertsCount,
            'compliance_due_soon' => $expiringComplianceCount,
        ];
        $summary = $getSummary->handle($organizationId, $counts);
        $summaryHtml = ($summary ?? 'Your fleet summary for today.')."<br><br>Active alerts: {$activeAlertsCount}. Compliance items expiring within 30 days: {$expiringComplianceCount}. Open work orders: {$openWorkOrdersCount}.";

        event(new FleetDailyDigestReady(
            organization: $organization,
            summaryHtml: $summaryHtml,
            activeAlertsCount: $activeAlertsCount,
            expiringComplianceCount: $expiringComplianceCount,
            openWorkOrdersCount: $openWorkOrdersCount,
        ));
    }
}
