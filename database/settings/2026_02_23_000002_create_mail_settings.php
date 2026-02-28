<?php

declare(strict_types=1);

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        $addIfMissing = function (string $key, mixed $value, bool $encrypted = false): void {
            if (! $this->migrator->exists($key)) {
                $encrypted ? $this->migrator->addEncrypted($key, $value) : $this->migrator->add($key, $value);
            }
        };

        $addIfMissing('mail.mailer', config('mail.default', 'log'));
        $addIfMissing('mail.smtp_host', config('mail.mailers.smtp.host', '127.0.0.1'));
        $addIfMissing('mail.smtp_port', (int) config('mail.mailers.smtp.port', 2525));
        $addIfMissing('mail.smtp_username', config('mail.mailers.smtp.username'));
        $addIfMissing('mail.smtp_password', config('mail.mailers.smtp.password'), true);
        $addIfMissing('mail.smtp_encryption', config('mail.mailers.smtp.scheme'));
        $addIfMissing('mail.from_address', config('mail.from.address', 'hello@example.com'));
        $addIfMissing('mail.from_name', config('mail.from.name', 'Example'));
    }
};
