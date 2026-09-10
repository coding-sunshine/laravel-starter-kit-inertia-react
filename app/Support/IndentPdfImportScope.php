<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\Siding;
use App\Models\User;

/**
 * Resolves which siding IDs may be used when parsing an e-Demand PDF for preview/import.
 * Matches web `IndentsController::importPreview` and API upload so behavior stays aligned.
 */
final class IndentPdfImportScope
{
    /**
     * @return list<int>
     */
    public static function allowedSidingIdsFor(User $user): array
    {
        if ($user->isSuperAdmin()) {
            return Siding::query()->pluck('id')->all();
        }

        $sidingIds = $user->sidings()->get()->pluck('id')->all();

        if ($sidingIds === [] && $user->siding_id !== null) {
            return [(int) $user->siding_id];
        }

        return $sidingIds;
    }
}
