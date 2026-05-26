<?php

declare(strict_types=1);

namespace App\Enums;

enum PasswordResetOtpStatus: string
{
    case Pending = 'pending';
    case Verified = 'verified';
    case Used = 'used';
    case Locked = 'locked';
    case Superseded = 'superseded';
}
