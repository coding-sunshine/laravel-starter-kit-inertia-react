<?php

declare(strict_types=1);

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        $this->migrator->add('billing.enable_seat_based_billing', false);
        $this->migrator->add('billing.allow_multiple_subscriptions', false);
    }
};
