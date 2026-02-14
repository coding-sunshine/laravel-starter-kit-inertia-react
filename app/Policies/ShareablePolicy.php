<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

final class ShareablePolicy
{
    /**
     * Determine if the user can share an item (create/update share records).
     * Laravel passes the shareable model (second gate argument) as the second parameter.
     */
    public function shareItem(User $user, Model $shareable): bool
    {
        if (! method_exists($shareable, 'canBeEditedBy')) {
            return false;
        }

        return $shareable->canBeEditedBy($user);
    }
}
