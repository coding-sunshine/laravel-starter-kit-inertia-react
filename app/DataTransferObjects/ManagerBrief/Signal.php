<?php

declare(strict_types=1);

namespace App\DataTransferObjects\ManagerBrief;

/**
 * Represents a single operational signal detected by CollectSignals.
 *
 * Signals are the raw pre-AI inputs to the ranking pipeline. Each signal
 * captures one operational event or trend (e.g. an overloaded wagon, a
 * silent Loadrite scale) along with its financial impact and actionability
 * score so that RankSignals can prioritise them before the AI agent narrates.
 *
 * Allowed `type` values:
 *   - 'overload_exposure'          – wagons at risk of penalty due to overloading
 *   - 'operator_anomaly'           – unusual loader-operator pattern detected
 *   - 'force_majeure'              – recovery opportunity from a force-majeure event
 *   - 'scale_silence'              – Loadrite scale has been silent / offline
 *   - 'pending_override'           – a loading override is awaiting approval
 *   - 'underloading_trend'         – consistent underloading against target tonnage
 *   - 'demurrage_risk'             – rake approaching demurrage threshold
 *   - 'operator_recurring_risk'    – operator with a persistent overload rate above threshold (forecast)
 *   - 'penalty_trajectory'         – month-on-month penalty Rs growing consecutively (forecast)
 *   - 'demurrage_turnaround_risk'  – average rake turnaround exceeding SLA (forecast)
 *   - 'pcc_drift'                  – wagon-type median load deviating from PCC (forecast)
 *   - 'data_quality_anomaly'       – open Loadrite anomaly count exceeds threshold (data quality)
 *   - 'billing_variance'           – billed penalty exceeds prediction; variance is recoverable (recovery)
 *
 * Allowed `severity` values: 'critical', 'high', 'medium', 'low'.
 */
final readonly class Signal
{
    /**
     * @param  array<string, mixed>  $payload  Type-specific extra detail (rake number, operator name, scale id, etc.)
     */
    public function __construct(
        public string $type,
        public string $severity,
        public float $rsAtStake,
        public int $recencyMinutes,
        public float $actionability,
        public array $payload,
    ) {}

    /**
     * Serialise to a plain array for Inertia transport or cache storage.
     *
     * @return array{type: string, severity: string, rs_at_stake: float, recency_minutes: int, actionability: float, payload: array<string, mixed>}
     */
    public function toArray(): array
    {
        return [
            'type' => $this->type,
            'severity' => $this->severity,
            'rs_at_stake' => $this->rsAtStake,
            'recency_minutes' => $this->recencyMinutes,
            'actionability' => $this->actionability,
            'payload' => $this->payload,
        ];
    }
}
