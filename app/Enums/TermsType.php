<?php

declare(strict_types=1);

namespace App\Enums;

enum TermsType: string
{
    case Terms = 'terms';
    case Privacy = 'privacy';

    public function label(): string
    {
        return match ($this) {
            self::Terms => __('Terms of Service'),
            self::Privacy => __('Privacy Policy'),
        };
    }
}
