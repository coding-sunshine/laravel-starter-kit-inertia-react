<?php

declare(strict_types=1);

namespace App\Events\User;

use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use MartinPetricko\LaravelDatabaseMail\Events\Concerns\CanTriggerDatabaseMail;
use MartinPetricko\LaravelDatabaseMail\Events\Contracts\TriggersDatabaseMail;
use MartinPetricko\LaravelDatabaseMail\Recipients\Recipient;

final class UserCreated implements TriggersDatabaseMail
{
    use CanTriggerDatabaseMail;
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public User $user
    ) {}

    public static function getDescription(): string
    {
        return 'Fires when a new user is registered.';
    }

    public static function getName(): string
    {
        return 'User created';
    }

    /**
     * @return array<string, Recipient<UserCreated>>
     */
    public static function getRecipients(): array
    {
        return [
            'user' => new Recipient('Registered user', fn (UserCreated $event): array => [$event->user]),
        ];
    }
}
