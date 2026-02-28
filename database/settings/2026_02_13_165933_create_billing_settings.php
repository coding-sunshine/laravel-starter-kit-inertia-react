<?php

declare(strict_types=1);

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        if (! $this->migrator->exists('billing.enable_seat_based_billing')) {
            $this->migrator->add('billing.enable_seat_based_billing', false);
        }
        if (! $this->migrator->exists('billing.allow_multiple_subscriptions')) {
            $this->migrator->add('billing.allow_multiple_subscriptions', false);
        }
    }
};
