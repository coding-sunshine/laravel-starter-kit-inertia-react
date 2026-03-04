<?php

declare(strict_types=1);

namespace App\Services\Fleet;

use App\Models\Fleet\Alert;
use App\Models\Fleet\ComplianceItem;
use App\Models\Fleet\ServiceSchedule;
use App\Models\Fleet\WorkOrder;
use App\Models\Scopes\OrganizationScope;

final class FleetInsightsService
{
    private const int MOT_DAYS_AHEAD = 14;

    /**
     * Return an array of short insight strings for the organization (e.g. "3 vehicles with MOT due in 14 days").
     *
     * @return array<int, string>
     */
    public function forOrganization(int $organizationId): array
    {
        $insights = [];

        $expiringQuery = (fn (): \Illuminate\Database\Eloquent\Builder => ComplianceItem::query()->withoutGlobalScope(OrganizationScope::class)
            ->where('organization_id', $organizationId)
            ->where('expiry_date', '>=', now())
            ->where('expiry_date', '<=', now()->addDays(self::MOT_DAYS_AHEAD)));

        // MOT due in next N days (proactive compliance insight)
        $motCount = $expiringQuery()->where('compliance_type', 'mot')->count();
        if ($motCount > 0) {
            $insights[] = sprintf('%d vehicle(s) with MOT due in the next %d days.', $motCount, self::MOT_DAYS_AHEAD);
        }

        // Insurance expiring in next N days
        $insuranceCount = $expiringQuery()->where('compliance_type', 'insurance')->count();
        if ($insuranceCount > 0) {
            $insights[] = sprintf('%d insurance (or policy) item(s) expiring in the next %d days.', $insuranceCount, self::MOT_DAYS_AHEAD);
        }

        // Other compliance (tax, licence, etc.) expiring in next N days
        $otherCount = $expiringQuery()
            ->whereNotIn('compliance_type', ['mot', 'insurance'])
            ->count();
        if ($otherCount > 0) {
            $insights[] = sprintf('%d other compliance item(s) (tax, licence, etc.) expiring in the next %d days.', $otherCount, self::MOT_DAYS_AHEAD);
        }

        // Service schedules due soon
        $serviceDueCount = ServiceSchedule::query()->withoutGlobalScope(OrganizationScope::class)
            ->where('organization_id', $organizationId)
            ->where('is_active', true)
            ->whereNotNull('next_service_due_date')
            ->where('next_service_due_date', '<=', now()->addDays(self::MOT_DAYS_AHEAD))
            ->where('next_service_due_date', '>=', now())
            ->count();
        if ($serviceDueCount > 0) {
            $insights[] = sprintf('%d service schedule(s) due in the next %d days.', $serviceDueCount, self::MOT_DAYS_AHEAD);
        }

        // Unacknowledged alerts
        $unackCount = Alert::query()->withoutGlobalScope(OrganizationScope::class)
            ->where('organization_id', $organizationId)
            ->whereNull('acknowledged_at')
            ->count();
        if ($unackCount > 0) {
            $insights[] = sprintf('%d unacknowledged alert(s).', $unackCount);
        }

        // Overdue work orders (open, due in the past)
        $overdueCount = WorkOrder::query()->withoutGlobalScope(OrganizationScope::class)
            ->where('organization_id', $organizationId)
            ->whereIn('status', ['open', 'pending', 'in_progress', 'scheduled'])
            ->whereNotNull('due_date')
            ->where('due_date', '<', \Illuminate\Support\Facades\Date::today())
            ->count();
        if ($overdueCount > 0) {
            $insights[] = sprintf('%d work order(s) overdue.', $overdueCount);
        }

        return $insights;
    }

    /**
     * Suggested questions for the assistant based on current insights.
     *
     * @return array<int, string>
     */
    public function suggestedQuestions(int $organizationId): array
    {
        $insights = $this->forOrganization($organizationId);
        $suggested = [];
        if ($insights !== []) {
            $suggested[] = 'What needs attention in my fleet right now?';
            $suggested[] = 'Which vehicles have MOT or insurance expiring soon?';
        }
        $suggested[] = 'Where is vehicle AB12 CDE?';
        $suggested[] = 'Show me open work orders.';
        $suggested[] = 'List vehicles and their compliance status.';

        return array_slice(array_unique($suggested), 0, 6);
    }
}
