<?php

declare(strict_types=1);

namespace App\Filament\Resources\ApprovedApiKeys\Pages;

use App\Filament\Resources\ApprovedApiKeys\ApprovedApiKeysResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

final class EditApprovedApiKeys extends EditRecord
{
    protected static string $resource = ApprovedApiKeysResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
