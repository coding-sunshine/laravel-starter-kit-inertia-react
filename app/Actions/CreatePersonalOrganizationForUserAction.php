<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Organization;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;

use function getPermissionsTeamId;
use function setPermissionsTeamId;

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

            $teamKey = config('permission.column_names.team_foreign_key');
            $guard = 'web';

            Role::query()->create([
                'name' => 'admin',
                'guard_name' => $guard,
                $teamKey => $organization->id,
            ]);
            Role::query()->create([
                'name' => 'member',
                'guard_name' => $guard,
                $teamKey => $organization->id,
            ]);

            $organization->users()->attach($user->id, [
                'is_default' => true,
                'joined_at' => now(),
                'invited_by' => null,
            ]);

            $previousTeamId = getPermissionsTeamId();
            setPermissionsTeamId($organization->id);
            try {
                $user->assignRole('admin');
            } finally {
                setPermissionsTeamId($previousTeamId);
            }

            return $organization;
        });
    }
}
