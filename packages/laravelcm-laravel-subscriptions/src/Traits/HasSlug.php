<?php

declare(strict_types=1);

namespace Laravelcm\Subscriptions\Traits;

use Spatie\Sluggable\HasSlug as SpatieHasSlug;

/**
 * Thin alias around Spatie's {@see SpatieHasSlug}.
 *
 * Spatie v4 registers slug generation in {@see SpatieHasSlug::bootHasSlug()};
 * the previous fork logic duplicated that and is incompatible with v4.
 */
trait HasSlug
{
    use SpatieHasSlug;
}
