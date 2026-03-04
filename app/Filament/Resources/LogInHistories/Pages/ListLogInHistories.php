<?php

declare(strict_types=1);

namespace App\Filament\Resources\LogInHistories\Pages;

use App\Filament\Resources\LogInHistories\LogInHistoryResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

final class ListLogInHistories extends ListRecords
{
    protected static string $resource = LogInHistoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
