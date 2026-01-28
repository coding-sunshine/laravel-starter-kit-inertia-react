<?php

declare(strict_types=1);

use App\Services\PrismService;

if (! function_exists('ai')) {
    /**
     * Get a PrismService instance for AI operations.
     */
    function ai(): PrismService
    {
        return new PrismService;
    }
}
