<?php

declare(strict_types=1);

namespace App\Observers;

use App\Actions\AddMentionAction;
use App\Models\CrmNote;
use App\Models\User;
use Throwable;

final class CrmNoteObserver
{
    public function __construct(private readonly AddMentionAction $addMention)
    {
        //
    }

    public function created(CrmNote $note): void
    {
        $this->processMentions($note);
    }

    public function updated(CrmNote $note): void
    {
        if (! $note->wasChanged('content')) {
            return;
        }

        $this->processMentions($note);
    }

    private function processMentions(CrmNote $note): void
    {
        if ($note->organization_id === null || $note->author_id === null) {
            return;
        }

        // Extract @username patterns from note content
        preg_match_all('/@([a-zA-Z0-9_]+)/', $note->content, $matches);

        $usernames = array_unique($matches[1] ?? []);

        if (empty($usernames)) {
            return;
        }

        foreach ($usernames as $username) {
            try {
                $user = User::query()
                    ->where('name', $username)
                    ->orWhere('email', 'like', $username.'%')
                    ->first();

                if ($user === null || $user->id === $note->author_id) {
                    continue;
                }

                $this->addMention->handle(
                    context: mb_substr($note->content, 0, 500),
                    mentionableId: $note->id,
                    mentionableType: CrmNote::class,
                    mentionedUserId: $user->id,
                    mentionedByUserId: $note->author_id,
                    organizationId: $note->organization_id,
                );
            } catch (Throwable) {
                // Don't fail note creation on mention errors
            }
        }
    }
}
