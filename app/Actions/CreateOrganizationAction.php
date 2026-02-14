<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Organization;
use App\Models\User;
use App\Services\TenantContext;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;

use function getPermissionsTeamId;
use function setPermissionsTeamId;

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

            $isDefault = $user->organizations()->count() === 0;
            $organization->users()->attach($user->id, [
                'is_default' => $isDefault,
                'joined_at' => now(),
                'invited_by' => null,
            ]);

            $previousContext = TenantContext::get();
            TenantContext::set($organization);
            $previousTeamId = getPermissionsTeamId();
            setPermissionsTeamId($organization->id);
            try {
                $user->assignRole('admin');
            } finally {
                setPermissionsTeamId($previousTeamId);
                if ($previousContext instanceof Organization) {
                    TenantContext::set($previousContext);
                } else {
                    TenantContext::forget();
                }
            }

            return $organization;
        });
    }
}
