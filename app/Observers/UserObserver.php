<?php

declare(strict_types=1);

namespace App\Observers;

use App\Events\User\UserCreated;
use App\Models\User;

final class UserObserver
{
    public function created(User $user): void
    {
        event(new UserCreated($user));
    }
}
