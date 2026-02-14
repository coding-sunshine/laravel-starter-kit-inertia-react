<?php

declare(strict_types=1);

namespace App\Events;

use App\Models\TermsVersion;
use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use MartinPetricko\LaravelDatabaseMail\Events\Concerns\CanTriggerDatabaseMail;
use MartinPetricko\LaravelDatabaseMail\Events\Contracts\TriggersDatabaseMail;
use MartinPetricko\LaravelDatabaseMail\Recipients\Recipient;

final class NewTermsVersionPublished implements TriggersDatabaseMail
{
    use CanTriggerDatabaseMail;
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public TermsVersion $termsVersion,
        public User $user
    ) {}

    public static function getDescription(): string
    {
        return 'Fires when a new terms version is published and a user must be notified (one event per user).';
    }

    public static function getName(): string
    {
        return 'New terms version published';
    }

    /**
     * @return array<string, Recipient<NewTermsVersionPublished>>
     */
    public static function getRecipients(): array
    {
        return [
            'user' => new Recipient('User to notify', fn (NewTermsVersionPublished $event): array => [$event->user]),
        ];
    }
}
