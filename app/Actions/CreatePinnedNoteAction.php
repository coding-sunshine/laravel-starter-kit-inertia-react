<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\PinnedNote;
use Illuminate\Database\Eloquent\Model;

/**
 * Create a pinned note for any noteable model.
 */
final class CreatePinnedNoteAction
{
    /**
     * @param  array<int, string>  $roleVisibility
     */
    public function handle(Model $noteable, string $content, array $roleVisibility = []): PinnedNote
    {
        /** @var int|null $organizationId */
        $organizationId = $noteable->getAttribute('organization_id');

        $maxOrder = PinnedNote::query()
            ->where('noteable_type', $noteable->getMorphClass())
            ->where('noteable_id', $noteable->getKey())
            ->max('order') ?? -1;

        /** @var PinnedNote $note */
        $note = PinnedNote::query()->create([
            'organization_id' => $organizationId,
            'noteable_type' => $noteable->getMorphClass(),
            'noteable_id' => $noteable->getKey(),
            'author_id' => auth()->id(),
            'content' => $content,
            'role_visibility' => $roleVisibility ?: null,
            'is_active' => true,
            'order' => $maxOrder + 1,
        ]);

        return $note;
    }
}
