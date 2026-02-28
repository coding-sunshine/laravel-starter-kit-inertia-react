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

        // Stripe
        $addIfMissing('stripe.key', config('stripe.key'), true);
        $addIfMissing('stripe.secret', config('stripe.secret'), true);
        $addIfMissing('stripe.webhook_secret', config('stripe.webhook_secret'), true);

        // Paddle
        $addIfMissing('paddle.vendor_id', config('paddle.vendor_id'), true);
        $addIfMissing('paddle.vendor_auth_code', config('paddle.vendor_auth_code'), true);
        $addIfMissing('paddle.public_key', config('paddle.public_key'), true);
        $addIfMissing('paddle.webhook_secret', config('paddle.webhook_secret'), true);
        $addIfMissing('paddle.sandbox', (bool) config('paddle.sandbox', true));

        // Lemon Squeezy
        $addIfMissing('lemon-squeezy.api_key', config('lemon-squeezy.api_key'), true);
        $addIfMissing('lemon-squeezy.signing_secret', config('lemon-squeezy.signing_secret'), true);
        $addIfMissing('lemon-squeezy.store', config('lemon-squeezy.store'));
        $addIfMissing('lemon-squeezy.path', config('lemon-squeezy.path', 'lemon-squeezy'));
        $addIfMissing('lemon-squeezy.currency_locale', config('lemon-squeezy.currency_locale', 'en'));
        $addIfMissing('lemon-squeezy.generic_variant_id', config('services.lemon_squeezy.generic_variant_id'));

        // Integrations
        $addIfMissing('integrations.slack_webhook_url', config('services.slack.webhook_url'), true);
        $addIfMissing('integrations.slack_bot_token', config('services.slack.notifications.bot_user_oauth_token'), true);
        $addIfMissing('integrations.slack_channel', config('services.slack.notifications.channel'));
        $addIfMissing('integrations.postmark_token', config('services.postmark.token'), true);
        $addIfMissing('integrations.resend_key', config('services.resend.key'), true);
    }
};
