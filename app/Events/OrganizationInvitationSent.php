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

final class OrganizationInvitationSent implements TriggersDatabaseMail
{
    use CanTriggerDatabaseMail;
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public OrganizationInvitation $invitation,
        public Organization $organization,
        public string $email,
        public string $role,
        public User $invitedBy
    ) {}

    public static function getDescription(): string
    {
        return 'Fires when an organization invitation is sent or resent to an email address.';
    }

    public static function getName(): string
    {
        return 'Organization invitation sent';
    }

    /**
     * @return array<string, Recipient<OrganizationInvitationSent>>
     */
    public static function getRecipients(): array
    {
        return [
            'invitee' => new Recipient('Invitee (by email)', fn (OrganizationInvitationSent $event): array => [$event->email]),
        ];
    }
}
