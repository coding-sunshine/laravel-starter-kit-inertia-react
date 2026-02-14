<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\TermsVersion;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

/**
 * Returns required terms versions that the user has not yet accepted.
 *
 * @return Collection<int, TermsVersion>
 */
final readonly class GetRequiredTermsVersionsForUser
{
    public function handle(User $user): Collection
    {
        $acceptedVersionIds = $user->termsAcceptances()->pluck('terms_version_id')->toArray();

        return TermsVersion::query()
            ->where('is_required', true)
            ->whereNotIn('id', $acceptedVersionIds)
            ->oldest('effective_at')
            ->get();
    }
}
