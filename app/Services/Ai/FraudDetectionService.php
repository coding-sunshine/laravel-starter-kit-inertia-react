<?php

declare(strict_types=1);

namespace App\Services\Ai;

use App\Ai\Agents\FuelFraudDetectionAgent;
use App\Models\Fleet\FuelTransaction;
use App\Models\Scopes\OrganizationScope;
use Carbon\CarbonInterface;
use Laravel\Ai\Responses\StructuredAgentResponse;

final class FraudDetectionService
{
    public function __construct(
        private readonly FuelFraudDetectionAgent $agent
    ) {}

    /**
     * Run fuel fraud detection for an organization. Safe to call from queued jobs.
     *
     * @return array{findings: array<int, array{transaction_id: int, fraud_score: float, reason: string, severity: string}>}
     */
    public function run(int $organizationId, ?CarbonInterface $dateFrom = null, ?CarbonInterface $dateTo = null): array
    {
        $context = $this->buildContext($organizationId, $dateFrom, $dateTo);
        $response = $this->agent->prompt($context);

        if (! $response instanceof StructuredAgentResponse) {
            return ['findings' => []];
        }

        $structured = $response->structured;
        $findings = $structured['findings'] ?? [];
        if (! is_array($findings)) {
            return ['findings' => []];
        }

        $normalized = [];
        foreach ($findings as $f) {
            if (! is_array($f)) {
                continue;
            }
            $transactionId = isset($f['transaction_id']) ? (int) $f['transaction_id'] : 0;
            if ($transactionId < 1) {
                continue;
            }
            $normalized[] = [
                'transaction_id' => $transactionId,
                'fraud_score' => (float) ($f['fraud_score'] ?? 0),
                'reason' => (string) ($f['reason'] ?? ''),
                'severity' => $this->normalizeSeverity((string) ($f['severity'] ?? 'low')),
            ];
        }

        return ['findings' => $normalized];
    }

    private function buildContext(int $organizationId, ?CarbonInterface $dateFrom, ?CarbonInterface $dateTo): string
    {
        $query = FuelTransaction::withoutGlobalScope(OrganizationScope::class)
            ->where('organization_id', $organizationId)
            ->orderBy('transaction_timestamp', 'desc');

        if ($dateFrom !== null) {
            $query->where('transaction_timestamp', '>=', $dateFrom);
        }
        if ($dateTo !== null) {
            $query->where('transaction_timestamp', '<=', $dateTo);
        }

        $str = static fn (mixed $v): ?string => $v === null ? null : (string) $v;

        $transactions = $query->limit(500)->get()->map(fn ($t) => [
            'id' => $t->id,
            'vehicle_id' => $t->vehicle_id,
            'driver_id' => $t->driver_id,
            'transaction_timestamp' => $t->transaction_timestamp?->toIso8601String(),
            'fuel_station_name' => $t->fuel_station_name,
            'fuel_station_address' => $t->fuel_station_address,
            'lat' => $str($t->lat),
            'lng' => $str($t->lng),
            'fuel_type' => $t->fuel_type,
            'litres' => $str($t->litres),
            'price_per_litre' => $str($t->price_per_litre),
            'total_cost' => $str($t->total_cost),
            'odometer_reading' => $t->odometer_reading,
            'validation_status' => $t->validation_status,
            'fraud_risk_score' => $str($t->fraud_risk_score),
            'anomaly_flags' => $t->anomaly_flags,
        ])->toArray();

        $json = ['fuel_transactions' => $transactions];
        return "Analyze these fuel transactions for anomalies suggesting possible fraud (location, time, volume, patterns). Return only the structured findings.\n\n" . json_encode($json, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    }

    private function normalizeSeverity(string $severity): string
    {
        $s = strtolower($severity);
        return in_array($s, ['low', 'medium', 'high', 'critical'], true) ? $s : 'low';
    }
}
