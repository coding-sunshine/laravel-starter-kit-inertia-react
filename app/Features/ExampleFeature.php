<?php

declare(strict_types=1);

namespace App\Features;

use Stephenjude\FilamentFeatureFlag\Traits\WithFeatureResolver;

final class ExampleFeature
{
    use WithFeatureResolver;

    public bool $defaultValue = true;
}
