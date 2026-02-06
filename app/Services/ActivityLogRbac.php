<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\ActivityType;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;

final readonly class ActivityLogRbac
{
    /**
     * @return array<int, string>
     */
    public static function roleNamesFrom(Model $user): array
    {
        return $user->roles->pluck('name')->values()->all();
    }

    /**
     * @return array<int, string>
     */
    public static function permissionNamesFrom(Model $role): array
    {
        return $role->permissions->pluck('name')->values()->all();
    }

    public function logRolesUpdated(Model $user, array $oldRoleNames, array $newRoleNames): void
    {
        if ($oldRoleNames === $newRoleNames) {
            return;
        }

        $causer = $this->resolveCauser();
        if ($causer === null) {
            return;
        }

        activity()
            ->performedOn($user)
            ->causedBy($causer)
            ->withProperties([
                'old' => $oldRoleNames,
                'attributes' => $newRoleNames,
            ])
            ->log(ActivityType::RolesUpdated->value);
    }

    public function logRolesAssigned(Model $user, array $roleNames): void
    {
        if ($roleNames === []) {
            return;
        }

        $causer = $this->resolveCauser();
        if ($causer === null) {
            return;
        }

        activity()
            ->performedOn($user)
            ->causedBy($causer)
            ->withProperties(['attributes' => $roleNames])
            ->log(ActivityType::RolesAssigned->value);
    }

    public function logPermissionsUpdated(Model $role, array $oldPermissionNames, array $newPermissionNames): void
    {
        if ($oldPermissionNames === $newPermissionNames) {
            return;
        }

        $causer = $this->resolveCauser();
        if ($causer === null) {
            return;
        }

        activity()
            ->performedOn($role)
            ->causedBy($causer)
            ->withProperties([
                'old' => $oldPermissionNames,
                'attributes' => $newPermissionNames,
            ])
            ->log(ActivityType::PermissionsUpdated->value);
    }

    public function logPermissionsAssigned(Model $role, array $permissionNames): void
    {
        if ($permissionNames === []) {
            return;
        }

        $causer = $this->resolveCauser();
        if ($causer === null) {
            return;
        }

        activity()
            ->performedOn($role)
            ->causedBy($causer)
            ->withProperties(['attributes' => $permissionNames])
            ->log(ActivityType::PermissionsAssigned->value);
    }

    private function resolveCauser(): ?Authenticatable
    {
        $user = auth()->user();

        return $user instanceof Authenticatable ? $user : null;
    }
}
