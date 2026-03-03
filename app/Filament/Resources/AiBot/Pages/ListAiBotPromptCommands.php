<?php

declare(strict_types=1);

namespace App\Filament\Resources\AiBot\Pages;

use App\Filament\Resources\AiBot\AiBotPromptCommandResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

final class ListAiBotPromptCommands extends ListRecords
{
    protected static string $resource = AiBotPromptCommandResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}
