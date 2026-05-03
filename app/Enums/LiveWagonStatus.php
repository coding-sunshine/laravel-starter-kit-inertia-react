<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Live operational status of a single wagon, derived from its current loading state.
 *
 * Used by the Control Room (Live Monitor) to color-code wagons in the train visualization.
 * Always derived — never persisted — so the same wagon can show different statuses as
 * loaded quantity changes.
 */
enum LiveWagonStatus: string
{
    case Empty = 'empty';
    case Loading = 'loading';
    case Loaded = 'loaded';
    case Overload = 'overload';
    case Unfit = 'unfit';

    public function label(): string
    {
        return match ($this) {
            self::Empty => 'Empty / Not Started',
            self::Loading => 'Loading',
            self::Loaded => 'Loaded',
            self::Overload => 'Over Load',
            self::Unfit => 'Unfit / Skip',
        };
    }

    /**
     * Hex color used by frontend renderers. Kept here so backend payloads can include
     * the colour authoritatively (avoids divergence with client-side palettes).
     */
    public function color(): string
    {
        return match ($this) {
            self::Empty => '#9CA3AF',     // gray-400
            self::Loading => '#F59E0B',   // amber-500
            self::Loaded => '#10B981',    // emerald-500
            self::Overload => '#EF4444',  // red-500
            self::Unfit => '#3B82F6',     // blue-500
        };
    }
}
