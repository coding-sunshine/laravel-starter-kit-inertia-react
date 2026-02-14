<?php

declare(strict_types=1);

namespace App\Actions\Fortify;

use App\Enums\ActivityType;
use Illuminate\Database\Eloquent\Model;
use Laravel\Fortify\Actions\EnableTwoFactorAuthentication;

final readonly class LoggingEnableTwoFactorAuthentication
{
    public function __construct(
        private EnableTwoFactorAuthentication $enable
    ) {}

    public function __invoke(Model $user, bool $force = false): void
    {
        ($this->enable)($user, $force);

        activity()
            ->performedOn($user)
            ->causedBy($user)
            ->log(ActivityType::TwoFactorEnabled->value);
    }
}
