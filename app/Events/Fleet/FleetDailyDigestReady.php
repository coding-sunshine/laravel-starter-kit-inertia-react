<?php

declare(strict_types=1);

namespace App\Events\Fleet;

use App\Models\Organization;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use MartinPetricko\LaravelDatabaseMail\Events\Concerns\CanTriggerDatabaseMail;
use MartinPetricko\LaravelDatabaseMail\Events\Contracts\TriggersDatabaseMail;
use MartinPetricko\LaravelDatabaseMail\Recipients\Recipient;

final class FleetDailyDigestReady implements TriggersDatabaseMail
{
    use CanTriggerDatabaseMail;
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public Organization $organization,
        public string $summaryHtml,
        public int $activeAlertsCount,
        public int $expiringComplianceCount,
        public int $openWorkOrdersCount,
    ) {}

    public static function getDescription(): string
    {
        return 'Fires when the daily fleet digest is ready to email (alerts, compliance, work orders summary).';
    }

    public static function getName(): string
    {
        return 'Fleet daily digest';
    }

    /**
     * @return array<string, Recipient<FleetDailyDigestReady>>
     */
    public static function getRecipients(): array
    {
        return [
            'owner' => new Recipient('Organization owner', function (FleetDailyDigestReady $event): array {
                $owner = $event->organization->owner;

                return $owner !== null ? [$owner] : [];
            }),
        ];
    }
}
