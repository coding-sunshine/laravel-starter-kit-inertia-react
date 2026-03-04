<?php

declare(strict_types=1);

namespace App\Filament\Resources\PIABAdmins\Pages;

use App\Filament\Resources\PIABAdmins\PIABAdminResource;
use Filament\Resources\Pages\ListRecords;

final class ListPIABAdmins extends ListRecords
{
    protected static string $resource = PIABAdminResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
