<?php

declare(strict_types=1);

namespace App\Filament\Resources\ApprovedApiKeys\Pages;

use App\Filament\Resources\ApprovedApiKeys\ApprovedApiKeysResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

final class ListApprovedApiKeys extends ListRecords
{
    protected static string $resource = ApprovedApiKeysResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
