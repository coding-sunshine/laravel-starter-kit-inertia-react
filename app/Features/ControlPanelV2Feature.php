<?php

declare(strict_types=1);

namespace App\Features;

use Stephenjude\FilamentFeatureFlag\Traits\WithFeatureResolver;

final class ControlPanelV2Feature
{
    use WithFeatureResolver;

    public bool $defaultValue = false;
}
