<?php

declare(strict_types=1);

namespace App\Features;

use Stephenjude\FilamentFeatureFlag\Traits\WithFeatureResolver;

final class ContactFeature
{
    use WithFeatureResolver;

    public bool $defaultValue = true;
}
