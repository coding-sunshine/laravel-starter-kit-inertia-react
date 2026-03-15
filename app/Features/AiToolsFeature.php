<?php

declare(strict_types=1);

namespace App\Features;

use Stephenjude\FilamentFeatureFlag\Traits\WithFeatureResolver;

final class AiToolsFeature
{
    use WithFeatureResolver;

    public bool $defaultValue = false;
}
