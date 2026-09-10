<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use Stephenjude\FilamentFeatureFlag\Resources\FeatureSegmentResource as BaseFeatureSegmentResource;

final class FeatureSegmentResource extends BaseFeatureSegmentResource
{
    protected static bool $shouldRegisterNavigation = false;
}
