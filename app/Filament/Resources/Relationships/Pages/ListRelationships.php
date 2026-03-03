<?php

declare(strict_types=1);

namespace App\Filament\Resources\Relationships\Pages;

use App\Filament\Resources\Relationships\RelationshipResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

final class ListRelationships extends ListRecords
{
    protected static string $resource = RelationshipResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
