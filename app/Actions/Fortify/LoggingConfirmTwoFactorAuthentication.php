<?php

declare(strict_types=1);

namespace App\Actions\Fortify;

use App\Enums\ActivityType;
use Illuminate\Database\Eloquent\Model;
use Laravel\Fortify\Actions\ConfirmTwoFactorAuthentication;

final class LoggingConfirmTwoFactorAuthentication
{
    public function __construct(
        private readonly ConfirmTwoFactorAuthentication $confirm
    ) {}

    public function __invoke(Model $user, string $code): void
    {
        ($this->confirm)($user, $code);

        activity()
            ->performedOn($user)
            ->causedBy($user)
            ->log(ActivityType::TwoFactorConfirmed->value);
    }
}
