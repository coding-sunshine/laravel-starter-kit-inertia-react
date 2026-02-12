<?php

declare(strict_types=1);

namespace App\Listeners\Gamification;

use App\Events\User\UserCreated;
use App\Features\GamificationFeature;
use Laravel\Pennant\Feature;
use Throwable;

final class GrantGamificationOnUserCreated
{
    public function handle(UserCreated $event): void
    {
        $user = $event->user;

        if (! Feature::for($user)->active(GamificationFeature::class)) {
            return;
        }

        try {
            $user->addPoints(10, reason: 'Signed up');
        } catch (Throwable) {
            // Fail silently so registration is never blocked
        }
    }
}
