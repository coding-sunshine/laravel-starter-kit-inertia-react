<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\User;

final readonly class CompleteOnboardingAction
{
    public function handle(User $user): void
    {
        $user->update(['onboarding_completed' => true]);
    }
}
