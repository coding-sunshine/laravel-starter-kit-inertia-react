<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Organization;
use App\Models\User;
use Illuminate\Support\Facades\DB;

final readonly class CreatePersonalOrganizationForUserAction
{
    /**
     * Create a personal organization for the user and add them as owner and admin.
     */
    public function handle(User $user): Organization
    {
        return DB::transaction(function () use ($user): Organization {
            $name = str_replace(
                '{name}',
                $user->name,
                config('tenancy.default_organization_name', "{name}'s Workspace")
            );

            $organization = Organization::query()->create([
                'name' => $name,
                'owner_id' => $user->id,
            ]);

            $organization->users()->attach($user->id, [
                'is_default' => true,
                'joined_at' => now(),
                'invited_by' => null,
            ]);

            $user->assignRole('admin');

            return $organization;
        });
    }
}
