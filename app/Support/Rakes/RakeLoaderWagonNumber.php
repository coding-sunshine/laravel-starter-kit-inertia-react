<?php

declare(strict_types=1);

namespace App\Support\Rakes;

/**
 * Wagon numbers matching weighment placeholders (e.g. W59) are excluded from rake-loader grids and progress.
 */
final class RakeLoaderWagonNumber
{
    /**
     * True when the number is a non-empty weighment-style placeholder (e.g. W1, W59), not a real wagon number.
     */
    public static function isWeighmentPlaceholder(?string $wagonNumber): bool
    {
        $trimmed = $wagonNumber !== null ? mb_trim($wagonNumber) : '';

        return $trimmed !== '' && preg_match('/^W\d+$/', $trimmed) === 1;
    }
}
