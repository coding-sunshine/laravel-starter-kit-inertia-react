<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Operational pipeline stage of a rake, derived from process timestamps.
 *
 * Distinct from the persisted `rakes.state` string column, which currently
 * carries values from two competing vocabularies (lifecycle + workflow-step).
 * See ADR: docs/architecture/ADRs/0001-canonical-rake-state.md.
 */
enum RakeLifecycleStage: string
{
    case AwaitingPlacement = 'awaiting_placement';
    case Loading = 'loading';
    case AwaitingDispatch = 'awaiting_dispatch';
    case Dispatched = 'dispatched';
    case InTransit = 'in_transit';
    case Delivered = 'delivered';

    public function label(): string
    {
        return match ($this) {
            self::AwaitingPlacement => 'Awaiting placement',
            self::Loading => 'Loading',
            self::AwaitingDispatch => 'Awaiting dispatch',
            self::Dispatched => 'Dispatched',
            self::InTransit => 'In transit',
            self::Delivered => 'Delivered',
        };
    }

    /**
     * True if the rake is part of the live operational pipeline (not yet completed).
     */
    public function isActive(): bool
    {
        return match ($this) {
            self::AwaitingPlacement,
            self::Loading,
            self::AwaitingDispatch => true,
            self::Dispatched,
            self::InTransit,
            self::Delivered => false,
        };
    }
}
