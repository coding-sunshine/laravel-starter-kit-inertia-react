<?php

declare(strict_types=1);

namespace App\Filament\Resources\AiBot\Pages;

use App\Filament\Resources\AiBot\AiBotBoxResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

final class ListAiBotBoxes extends ListRecords
{
    protected static string $resource = AiBotBoxResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}
