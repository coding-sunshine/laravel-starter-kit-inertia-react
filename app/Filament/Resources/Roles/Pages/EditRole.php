<?php

declare(strict_types=1);

namespace App\Filament\Resources\Roles\Pages;

use App\Filament\Resources\Roles\RoleResource;
use App\Services\ActivityLogRbac;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

final class EditRole extends EditRecord
{
    protected static string $resource = RoleResource::class;

    /**
     * @var array<int, string>
     */
    private array $previousPermissionNames = [];

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }

    protected function beforeSave(): void
    {
        $this->previousPermissionNames = ActivityLogRbac::permissionNamesFrom($this->record);
    }

    protected function afterSave(): void
    {
        $this->record->load('permissions');
        app(ActivityLogRbac::class)->logPermissionsUpdated(
            $this->record,
            $this->previousPermissionNames,
            ActivityLogRbac::permissionNamesFrom($this->record)
        );
    }
}
