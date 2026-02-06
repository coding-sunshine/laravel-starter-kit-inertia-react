<?php

declare(strict_types=1);

namespace App\Filament\Resources\Users\Pages;

use App\Filament\Resources\Users\UserResource;
use App\Services\ActivityLogRbac;
use Filament\Resources\Pages\CreateRecord;
use Spatie\Permission\Models\Role;

final class CreateUser extends CreateRecord
{
    protected static string $resource = UserResource::class;

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $roles = $data['roles'] ?? [];
        if ($roles === [] || $roles === null) {
            $defaultRoleName = config('permission.default_role');
            if (is_string($defaultRoleName) && $defaultRoleName !== '') {
                $defaultRole = Role::query()->where('name', $defaultRoleName)->first();
                if ($defaultRole !== null) {
                    $data['roles'] = [$defaultRole->getKey()];
                }
            }
        }

        return $data;
    }

    protected function afterCreate(): void
    {
        $this->record->load('roles');
        app(ActivityLogRbac::class)->logRolesAssigned(
            $this->record,
            ActivityLogRbac::roleNamesFrom($this->record)
        );
    }
}
