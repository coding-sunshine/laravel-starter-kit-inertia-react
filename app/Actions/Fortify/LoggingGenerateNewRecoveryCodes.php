<?php

declare(strict_types=1);

namespace App\Actions\Fortify;

use App\Enums\ActivityType;
use Illuminate\Database\Eloquent\Model;
use Laravel\Fortify\Actions\GenerateNewRecoveryCodes;

final class LoggingGenerateNewRecoveryCodes
{
    public function __construct(
        private readonly GenerateNewRecoveryCodes $generate
    ) {}

    public function __invoke(Model $user): void
    {
        ($this->generate)($user);

        activity()
            ->performedOn($user)
            ->causedBy($user)
            ->log(ActivityType::RecoveryCodesRegenerated->value);
    }
}
