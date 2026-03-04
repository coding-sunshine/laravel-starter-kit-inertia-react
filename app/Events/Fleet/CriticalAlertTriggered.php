<?php

declare(strict_types=1);

namespace App\Events\Fleet;

use App\Models\Fleet\Alert;
use App\Models\Organization;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use MartinPetricko\LaravelDatabaseMail\Events\Concerns\CanTriggerDatabaseMail;
use MartinPetricko\LaravelDatabaseMail\Events\Contracts\TriggersDatabaseMail;
use MartinPetricko\LaravelDatabaseMail\Recipients\Recipient;

final class CriticalAlertTriggered implements TriggersDatabaseMail
{
    use CanTriggerDatabaseMail;
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public Alert $alert,
        public Organization $organization,
    ) {}

    public static function getDescription(): string
    {
        return 'Fires when a critical or emergency fleet alert is created. Use to send immediate email/SMS to org owner or fleet managers.';
    }

    public static function getName(): string
    {
        return 'Critical fleet alert';
    }

    /**
     * @return array<string, Recipient<CriticalAlertTriggered>>
     */
    public static function getRecipients(): array
    {
        return [
            'owner' => new Recipient('Organization owner', function (CriticalAlertTriggered $event): array {
                $owner = $event->organization->owner;

                return $owner !== null ? [$owner] : [];
            }),
        ];
    }
}
