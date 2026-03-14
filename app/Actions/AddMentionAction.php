<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Mention;
use App\Models\User;
use App\Notifications\MentionNotification;

/**
 * Create a mention record and notify the mentioned user.
 */
final readonly class AddMentionAction
{
    public function handle(
        string $context,
        int $mentionableId,
        string $mentionableType,
        int $mentionedUserId,
        int $mentionedByUserId,
        int $organizationId,
    ): Mention {
        /** @var Mention $mention */
        $mention = Mention::query()->create([
            'context' => $context,
            'mentionable_id' => $mentionableId,
            'mentionable_type' => $mentionableType,
            'mentioned_user_id' => $mentionedUserId,
            'mentioned_by_user_id' => $mentionedByUserId,
            'organization_id' => $organizationId,
            'notified_at' => now(),
        ]);

        $mentionedUser = User::query()->find($mentionedUserId);

        if ($mentionedUser !== null) {
            $mentionedUser->notify(new MentionNotification($mention));
        }

        return $mention;
    }
}
