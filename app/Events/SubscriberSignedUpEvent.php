<?php

declare(strict_types=1);

namespace App\Events;

use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use MartinPetricko\LaravelDatabaseMail\Events\Concerns\CanTriggerDatabaseMail;
use MartinPetricko\LaravelDatabaseMail\Events\Contracts\TriggersDatabaseMail;
use MartinPetricko\LaravelDatabaseMail\Recipients\Recipient;

final class SubscriberSignedUpEvent implements TriggersDatabaseMail
{
    use CanTriggerDatabaseMail;
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public User $user,
        public Organization $org,
        public string $planSlug,
    ) {}

    public static function getName(): string
    {
        return 'Subscriber signed up';
    }

    public static function getDescription(): string
    {
        return 'Fires when a new subscriber completes self-service signup and org is provisioned.';
    }

    /**
     * @return array<string, Recipient<SubscriberSignedUpEvent>>
     */
    public static function getRecipients(): array
    {
        return [
            'subscriber' => new Recipient('New subscriber', fn (SubscriberSignedUpEvent $event): array => [$event->user]),
        ];
    }
}
