<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\Rake;
use App\Models\Weighment;

/**
 * Central rules for which RRMCS records are "standalone / historical" vs operational system data.
 * Used by delete actions to choose full cleanup vs document-only removal.
 */
final class RrmcsDeletionRules
{
    /**
     * Rake rows created only to hold historical weighment PDF imports (not live workflow).
     */
    public static function isStandaloneHistoricalWeighmentRake(Rake $rake): bool
    {
        return $rake->data_source === 'historical_weighment_pdf';
    }

    /**
     * After removing an {@see RrDocument}, the rake shell may be removed when it exists only for historical RR import.
     */
    public static function shouldDeleteRakeAfterRemovingHistoricalRr(Rake $rake): bool
    {
        return $rake->data_source === 'historical_rr';
    }

    /**
     * Weighment row is allowed to be removed via standalone weighments delete (entire historical rake bundle).
     */
    public static function isWeighmentDeletableFromStandaloneModule(Weighment $weighment): bool
    {
        $rake = $weighment->relationLoaded('rake') ? $weighment->rake : $weighment->rake()->first();

        if ($rake === null) {
            return false;
        }

        return self::isStandaloneHistoricalWeighmentRake($rake);
    }
}
