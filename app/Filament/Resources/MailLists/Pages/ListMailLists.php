<?php

declare(strict_types=1);

namespace App\Filament\Resources\MailLists\Pages;

use App\Filament\Resources\MailLists\MailListResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

final class ListMailLists extends ListRecords
{
    protected static string $resource = MailListResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
