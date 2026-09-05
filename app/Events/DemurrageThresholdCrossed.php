<?php

declare(strict_types=1);

namespace App\Events;

use App\Models\Rake;
use App\Models\Siding;
use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use MartinPetricko\LaravelDatabaseMail\Events\Concerns\CanTriggerDatabaseMail;
use MartinPetricko\LaravelDatabaseMail\Events\Contracts\TriggersDatabaseMail;
use MartinPetricko\LaravelDatabaseMail\Recipients\Recipient;

/**
 * Fired when a loading rake crosses a demurrage time threshold (60 / 30 / 0 min remaining).
 */
final class DemurrageThresholdCrossed implements TriggersDatabaseMail
{
    use CanTriggerDatabaseMail;
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public Rake $rake,
        /** The threshold type: 'demurrage_60', 'demurrage_30', or 'demurrage_0' */
        public string $threshold,
        public int $remainingMinutes,
        public float $projectedPenalty,
    ) {}

    public static function getDescription(): string
    {
        return 'Fires when a loading rake crosses a demurrage free-time threshold (60 / 30 / 0 minutes remaining).';
    }

    public static function getName(): string
    {
        return 'Demurrage threshold crossed';
    }

    /**
     * @return array<string, Recipient<DemurrageThresholdCrossed>>
     */
    public static function getRecipients(): array
    {
        return [
            'siding_users' => new Recipient(
                'Users assigned to the rake\'s siding',
                fn (DemurrageThresholdCrossed $event): array => self::resolveSidingUsers($event),
            ),
        ];
    }

    /**
     * @return array<User>
     */
    private static function resolveSidingUsers(self $event): array
    {
        $siding = $event->rake->siding;
        if (! $siding instanceof Siding) {
            return [];
        }

        return $siding->users()->get()->all();
    }
}
