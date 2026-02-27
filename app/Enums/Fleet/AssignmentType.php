<?php

declare(strict_types=1);

namespace App\Enums\Fleet;

enum AssignmentType: string
{
    case Primary = 'primary';
    case Secondary = 'secondary';
    case Temporary = 'temporary';
}
