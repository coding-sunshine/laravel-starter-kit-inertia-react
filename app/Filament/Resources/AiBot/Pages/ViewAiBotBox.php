<?php

declare(strict_types=1);

namespace App\Filament\Resources\AiBot\Pages;

use App\Filament\Resources\AiBot\AiBotBoxResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

final class ViewAiBotBox extends ViewRecord
{
    protected static string $resource = AiBotBoxResource::class;

    protected function getHeaderActions(): array
    {
        return [EditAction::make()];
    }
}
