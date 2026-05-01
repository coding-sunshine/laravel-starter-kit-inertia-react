<?php

declare(strict_types=1);

namespace App\Actions;

/**
 * Provisional rule per umbrella spec §5.2 — the formula may be rewritten
 * once the calibration corpus reveals the actual IR mechanism. The DTO
 * shape is the contract; the calculation inside CalculatePloPenaltyAction
 * is what changes if calibration disagrees.
 */
final readonly class PloPenaltyResult
{
    public function __construct(
        public int $rakeId,
        public float $chargeableWeightMt,
        public float $totalLoadedWeightMt,
        public float $shortfallMt,
        public float $rate,
        public float $amount,
    ) {}

    public function isApplicable(): bool
    {
        return $this->amount > 0.0;
    }
}
