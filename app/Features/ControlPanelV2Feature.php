<?php

declare(strict_types=1);

namespace App\Features;

use Stephenjude\FilamentFeatureFlag\Traits\WithFeatureResolver;

final class ControlPanelV2Feature
{
    use WithFeatureResolver;

    /**
     * On by default. The "Control Panel v2" nav item is additionally gated by
     * the sections.live_monitor.view permission, so this exposes v2 to exactly
     * the same audience as the legacy Control Room. Restrict further via a
     * Feature Segment in the Filament Feature Flags UI if needed.
     */
    public bool $defaultValue = true;
}
