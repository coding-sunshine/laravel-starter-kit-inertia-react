<?php

declare(strict_types=1);

namespace App\Jobs\Fleet;

use App\Models\Fleet\AiAnalysisResult;
use App\Models\Fleet\FuelTransaction;
use App\Models\Fleet\Vehicle;
use App\Services\TenantContext;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

final class DetectFleetAnomaliesJob implements ShouldQueue
{
    use Queueable;

    private const float FUEL_SPIKE_THRESHOLD = 1.5; // 50% increase

    public function handle(): void
    {
        $organizationIds = Vehicle::query()->distinct()->pluck('organization_id');

        foreach ($organizationIds as $organizationId) {
            try {
                $this->detectForOrganization($organizationId);
            } catch (Throwable $e) {
                Log::error('Fleet anomaly detection failed for organization', [
                    'organization_id' => $organizationId,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    private function detectForOrganization(int $organizationId): void
    {
        TenantContext::set($organizationId);

        $last7 = FuelTransaction::query()
            ->where('organization_id', $organizationId)
            ->where('transaction_timestamp', '>=', now()->subDays(7))
            ->sum(DB::raw('COALESCE(total_cost, 0)'));
        $prev7 = FuelTransaction::query()
            ->where('organization_id', $organizationId)
            ->whereBetween('transaction_timestamp', [now()->subDays(14), now()->subDays(8)])
            ->sum(DB::raw('COALESCE(total_cost, 0)'));

        if ($prev7 > 0 && $last7 >= $prev7 * self::FUEL_SPIKE_THRESHOLD) {
            AiAnalysisResult::query()->create([
                'organization_id' => $organizationId,
                'analysis_type' => 'cost_optimization',
                'entity_type' => 'organization',
                'entity_id' => $organizationId,
                'model_name' => 'fleet_anomaly_detection',
                'model_version' => '1.0',
                'confidence_score' => 0.85,
                'risk_score' => 0,
                'priority' => 'medium',
                'primary_finding' => sprintf(
                    'Fuel spend in the last 7 days (%.2f) is %.0f%% higher than the previous 7 days (%.2f).',
                    $last7,
                    (($last7 / $prev7) - 1) * 100,
                    $prev7,
                ),
                'detailed_analysis' => ['last_7_days' => $last7, 'previous_7_days' => $prev7],
                'recommendations' => ['Review fuel transactions and routes for cost optimization.'],
                'status' => 'pending',
            ]);
        }
    }
}
