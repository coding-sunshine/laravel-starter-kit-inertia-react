<?php

declare(strict_types=1);

namespace App\Events;

use App\Models\Commission;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use MartinPetricko\LaravelDatabaseMail\Concerns\CanTriggerDatabaseMail;
use MartinPetricko\LaravelDatabaseMail\Contracts\TriggersDatabaseMail;

final class CommissionUpdatedEvent implements TriggersDatabaseMail
{
    use CanTriggerDatabaseMail;
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    public function __construct(public readonly Commission $commission) {}

    public static function getName(): string
    {
        return 'Commission Updated';
    }

    public static function getDescription(): string
    {
        return 'Triggered when a commission record is updated (status change, payment, etc.)';
    }

    public static function getRecipients(self $event): array
    {
        $recipients = [];
        $agent = $event->commission->agentUser;
        if ($agent !== null) {
            $recipients[] = $agent;
        }

        return $recipients;
    }
}
