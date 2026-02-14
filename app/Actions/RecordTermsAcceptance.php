<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\TermsVersion;
use App\Models\User;
use App\Models\UserTermsAcceptance;
use Illuminate\Http\Request;

final readonly class RecordTermsAcceptance
{
    public function handle(User $user, TermsVersion $termsVersion, ?Request $request = null): UserTermsAcceptance
    {
        $acceptedAt = now();
        $ip = $request?->ip();
        $userAgent = $request?->userAgent();

        return UserTermsAcceptance::query()->create([
            'user_id' => $user->id,
            'terms_version_id' => $termsVersion->id,
            'accepted_at' => $acceptedAt,
            'ip' => $ip ? mb_substr($ip, 0, 45) : null,
            'user_agent' => $userAgent,
        ]);
    }
}
