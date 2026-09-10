<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\RrDocument;
use App\Models\User;

final class RrDocumentPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('rr-documents:view');
    }

    public function view(User $user, RrDocument $rrDocument): bool
    {
        return $user->can('view', $rrDocument->rake);
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('rr-documents:upload');
    }

    public function update(User $user, RrDocument $rrDocument): bool
    {
        return $user->can('update', $rrDocument->rake);
    }

    public function delete(User $user, RrDocument $rrDocument): bool
    {
        return $user->can('update', $rrDocument->rake);
    }
}
