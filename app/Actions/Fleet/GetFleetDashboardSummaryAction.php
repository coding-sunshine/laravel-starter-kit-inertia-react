<?php

declare(strict_types=1);

namespace App\Actions\Fleet;

use App\Services\PrismService;
use Illuminate\Support\Facades\Cache;
use Throwable;

final readonly class GetFleetDashboardSummaryAction
{
    private const int CACHE_TTL = 3600; // 1 hour

    public function __construct(
        private PrismService $prism,
    ) {}

    /**
     * Return 2–3 sentence AI summary of fleet status for the dashboard. Cached 1 hour.
     *
     * @param  array<string, int>  $counts  Key fleet counts (vehicles, drivers, work_orders, alerts_open, etc.)
     */
    public function handle(int $organizationId, array $counts): ?string
    {
        $cacheKey = "fleet_dashboard_ai_summary:{$organizationId}:".md5(json_encode($counts));

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($counts): ?string {
            if (! $this->prism->isAvailable()) {
                return null;
            }

            $vehicles = $counts['vehicles'] ?? 0;
            $drivers = $counts['drivers'] ?? 0;
            $workOrders = $counts['work_orders'] ?? 0;
            $alertsOpen = $counts['alerts_open'] ?? 0;
            $complianceDueSoon = $counts['compliance_due_soon'] ?? 0;

            $prompt = sprintf(
                "In 2-3 short sentences, summarize this fleet's status for a dashboard. Fleet has %d vehicles, %d drivers, %d work orders, %d active alerts, %d compliance items due soon. Be concise and actionable.",
                $vehicles,
                $drivers,
                $workOrders,
                $alertsOpen,
                $complianceDueSoon,
            );

            try {
                $response = $this->prism->generate($prompt);

                return mb_trim($response->text());
            } catch (Throwable) {
                return null;
            }
        });
    }
}
