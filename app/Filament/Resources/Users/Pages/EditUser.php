<?php

declare(strict_types=1);

namespace App\Filament\Resources\Users\Pages;

use App\Filament\Resources\Users\UserResource;
use App\Services\ActivityLogRbac;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Role;

final class EditUser extends EditRecord
{
    protected static string $resource = UserResource::class;

    /**
     * @var array<int, string>
     */
    private array $previousRoleNames = [];

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeSave(array $data): array
    {
        $user = $this->getRecord();
        if (! $user->isLastSuperAdmin() || ! $user->hasRole('super-admin')) {
            return $data;
        }

        $superAdminRole = Role::query()->where('name', 'super-admin')->first();
        if ($superAdminRole === null) {
            return $data;
        }

        $newRoleIds = $data['roles'] ?? [];
        $hasSuperAdmin = is_array($newRoleIds) && in_array($superAdminRole->getKey(), $newRoleIds, true);
        if (! $hasSuperAdmin) {
            throw ValidationException::withMessages([
                'roles' => ['Cannot remove the super-admin role from the last super-admin user.'],
            ]);
        }

        return $data;
    }

    protected function beforeSave(): void
    {
        $this->previousRoleNames = ActivityLogRbac::roleNamesFrom($this->record);
    }

    protected function afterSave(): void
    {
        $this->record->load('roles');
        app(ActivityLogRbac::class)->logRolesUpdated(
            $this->record,
            $this->previousRoleNames,
            ActivityLogRbac::roleNamesFrom($this->record)
        );
    }
}
