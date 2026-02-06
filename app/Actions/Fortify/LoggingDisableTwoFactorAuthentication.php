<?php

declare(strict_types=1);

namespace App\Actions\Fortify;

use App\Enums\ActivityType;
use Illuminate\Database\Eloquent\Model;
use Laravel\Fortify\Actions\DisableTwoFactorAuthentication;

final class LoggingDisableTwoFactorAuthentication
{
    public function __construct(
        private readonly DisableTwoFactorAuthentication $disable
    ) {}

    public function __invoke(Model $user): void
    {
        ($this->disable)($user);

        activity()
            ->performedOn($user)
            ->causedBy($user)
            ->log(ActivityType::TwoFactorDisabled->value);
    }
}
