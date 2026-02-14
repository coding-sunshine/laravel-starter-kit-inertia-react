<?php

declare(strict_types=1);

namespace App\Listeners\Billing;

use App\Actions\Billing\SyncSubscriptionSeatsAction;
use App\Events\OrganizationMemberAdded;
use App\Events\OrganizationMemberRemoved;

final readonly class SyncSubscriptionSeatsOnMemberChange
{
    public function __construct(
        private SyncSubscriptionSeatsAction $syncSeats
    ) {}

    public function handle(OrganizationMemberAdded|OrganizationMemberRemoved $event): void
    {
        $this->syncSeats->handle($event->organization);
    }
}
