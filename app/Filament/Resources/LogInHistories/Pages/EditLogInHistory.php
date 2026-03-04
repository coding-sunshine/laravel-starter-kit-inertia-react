<?php

declare(strict_types=1);

namespace App\Filament\Resources\LogInHistories\Pages;

use App\Filament\Resources\LogInHistories\LogInHistoryResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

final class EditLogInHistory extends EditRecord
{
    protected static string $resource = LogInHistoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
