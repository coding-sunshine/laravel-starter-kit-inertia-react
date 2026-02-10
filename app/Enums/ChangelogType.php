<?php

declare(strict_types=1);

namespace App\Enums;

enum ChangelogType: string
{
    case Added = 'added';
    case Changed = 'changed';
    case Fixed = 'fixed';
    case Removed = 'removed';
    case Security = 'security';
}
