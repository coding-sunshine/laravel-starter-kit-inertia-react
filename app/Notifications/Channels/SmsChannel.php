<?php

declare(strict_types=1);

namespace App\Notifications\Channels;

use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Log;

/**
 * Stub SMS channel. Logs outbound SMS when no driver is configured.
 * To enable real SMS: install an SMS provider (e.g. Twilio), set config,
 * and send in this channel instead of logging.
 */
final class SmsChannel
{
    public function send(object $notifiable, Notification $notification): void
    {
        if (! method_exists($notification, 'toSms')) {
            return;
        }
        $message = $notification->toSms($notifiable);
        $phone = $notifiable->phone ?? null;

        if ($phone === null || $phone === '') {
            return;
        }

        if (config('services.sms.driver') === 'twilio') {
            // TODO: resolve Twilio client and send: $client->messages->create($phone, ['body' => $message]);
            Log::channel('stack')->info('SMS (Twilio not wired):', [
                'to' => $phone,
                'body' => $message,
            ]);

            return;
        }

        Log::channel('stack')->info('SMS (stub):', [
            'to' => $phone,
            'body' => $message,
        ]);
    }
}
