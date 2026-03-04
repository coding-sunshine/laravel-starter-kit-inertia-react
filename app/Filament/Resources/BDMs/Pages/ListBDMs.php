<?php

declare(strict_types=1);

namespace App\Filament\Resources\BDMs\Pages;

use App\Filament\Resources\BDMs\BDMResource;
use Filament\Resources\Pages\ListRecords;

final class ListBDMs extends ListRecords
{
    protected static string $resource = BDMResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
