<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Organization;
use App\Models\User;
use Illuminate\Support\Facades\DB;

final readonly class CreateOrganizationAction
{
    /**
     * Create a new organization with the given name and set the user as owner and admin.
     */
    public function handle(User $user, string $name): Organization
    {
        return DB::transaction(function () use ($user, $name): Organization {
            $organization = Organization::query()->create([
                'name' => $name,
                'owner_id' => $user->id,
            ]);

            $isDefault = $user->organizations()->count() === 0;
            $organization->users()->attach($user->id, [
                'is_default' => $isDefault,
                'joined_at' => now(),
                'invited_by' => null,
            ]);

            $user->assignRole('admin');

            return $organization;
        });
    }
}
