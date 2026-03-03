<?php

declare(strict_types=1);

namespace App\Filament\Resources\AiBot\Pages;

use App\Filament\Resources\AiBot\AiBotBoxResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

final class EditAiBotBox extends EditRecord
{
    protected static string $resource = AiBotBoxResource::class;

    protected function getHeaderActions(): array
    {
        return [DeleteAction::make()];
    }
}
