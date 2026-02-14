<?php

declare(strict_types=1);

namespace App\Events;

use App\Models\Organization;
use App\Models\OrganizationInvitation;
use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use MartinPetricko\LaravelDatabaseMail\Events\Concerns\CanTriggerDatabaseMail;
use MartinPetricko\LaravelDatabaseMail\Events\Contracts\TriggersDatabaseMail;
use MartinPetricko\LaravelDatabaseMail\Recipients\Recipient;

final class OrganizationInvitationAccepted implements TriggersDatabaseMail
{
    use CanTriggerDatabaseMail;
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public OrganizationInvitation $invitation,
        public Organization $organization,
        public User $user,
        public string $role
    ) {}

    public static function getDescription(): string
    {
        return 'Fires when a user accepts an organization invitation (notify the inviter).';
    }

    public static function getName(): string
    {
        return 'Organization invitation accepted';
    }

    /**
     * @return array<string, Recipient<OrganizationInvitationAccepted>>
     */
    public static function getRecipients(): array
    {
        return [
            'inviter' => new Recipient('Inviter', fn (OrganizationInvitationAccepted $event): array => [$event->invitation->inviter]),
        ];
    }
}
