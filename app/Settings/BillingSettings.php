<?php

declare(strict_types=1);

namespace App\Settings;

use Spatie\LaravelSettings\Settings;

final class BillingSettings extends Settings
{
    public bool $enable_seat_based_billing = false;

    public bool $allow_multiple_subscriptions = false;

    public static function group(): string
    {
        return 'billing';
    }
}
