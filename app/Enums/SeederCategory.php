<?php

declare(strict_types=1);

namespace App\Enums;

enum SeederCategory: string
{
    case Essential = 'essential';
    case Development = 'development';
    case Production = 'production';
}
