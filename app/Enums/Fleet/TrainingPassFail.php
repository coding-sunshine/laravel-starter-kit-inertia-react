<?php

declare(strict_types=1);

namespace App\Enums\Fleet;

enum TrainingPassFail: string
{
    case Pass = 'pass';
    case Fail = 'fail';
    case Pending = 'pending';
    case NotRequired = 'not_required';
}
