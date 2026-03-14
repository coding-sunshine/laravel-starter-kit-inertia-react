<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\ListingVersion;
use App\Models\Lot;
use App\Models\Project;
use Illuminate\Support\Facades\Auth;

final readonly class CreateListingVersionAction
{
    /**
     * Snapshot the current state of a listing and create a new version record.
     */
    public function handle(Lot|Project $listing, ?string $changeSummary = null): ListingVersion
    {
        $lastVersion = ListingVersion::query()
            ->where('listable_type', $listing->getMorphClass())
            ->where('listable_id', $listing->getKey())
            ->max('version') ?? 0;

        return ListingVersion::query()->create([
            'listable_type' => $listing->getMorphClass(),
            'listable_id' => $listing->getKey(),
            'version' => $lastVersion + 1,
            'snapshot' => $listing->toArray(),
            'change_summary' => $changeSummary,
            'created_by' => Auth::id(),
        ]);
    }
}
