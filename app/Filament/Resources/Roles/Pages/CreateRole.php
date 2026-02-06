<?php

declare(strict_types=1);

namespace App\Filament\Resources\Roles\Pages;

use App\Filament\Resources\Roles\RoleResource;
use App\Services\ActivityLogRbac;
use Filament\Resources\Pages\CreateRecord;

final class CreateRole extends CreateRecord
{
    protected static string $resource = RoleResource::class;

    protected function afterCreate(): void
    {
        $this->record->load('permissions');
        app(ActivityLogRbac::class)->logPermissionsAssigned(
            $this->record,
            ActivityLogRbac::permissionNamesFrom($this->record)
        );
    }
}
