<?php

declare(strict_types=1);

namespace App\Events;

use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use MartinPetricko\LaravelDatabaseMail\Events\Concerns\CanTriggerDatabaseMail;
use MartinPetricko\LaravelDatabaseMail\Events\Contracts\TriggersDatabaseMail;
use MartinPetricko\LaravelDatabaseMail\Recipients\Recipient;

final class OnboardingReminderEvent implements TriggersDatabaseMail
{
    use CanTriggerDatabaseMail;
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public User $user,
        public int $completedSteps,
        public int $totalSteps,
    ) {}

    public static function getName(): string
    {
        return 'Onboarding reminder';
    }

    public static function getDescription(): string
    {
        return 'Fires weekly when a subscriber has incomplete onboarding steps.';
    }

    /**
     * @return array<string, Recipient<OnboardingReminderEvent>>
     */
    public static function getRecipients(): array
    {
        return [
            'subscriber' => new Recipient('Subscriber with incomplete onboarding', fn (OnboardingReminderEvent $event): array => [$event->user]),
        ];
    }
}
