<?php

declare(strict_types=1);

namespace App\Filament\Resources\AiBot\Pages;

use App\Filament\Resources\AiBot\AiBotPromptCommandResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

final class EditAiBotPromptCommand extends EditRecord
{
    protected static string $resource = AiBotPromptCommandResource::class;

    protected function getHeaderActions(): array
    {
        return [DeleteAction::make()];
    }
}
