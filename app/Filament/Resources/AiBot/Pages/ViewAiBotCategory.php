<?php

declare(strict_types=1);

namespace App\Filament\Resources\AiBot\Pages;

use App\Filament\Resources\AiBot\AiBotCategoryResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

final class ViewAiBotCategory extends ViewRecord
{
    protected static string $resource = AiBotCategoryResource::class;

    protected function getHeaderActions(): array
    {
        return [EditAction::make()];
    }
}
