<?php

declare(strict_types=1);

namespace App\Filament\Resources\NetworkActivities\Pages;

use App\Filament\Resources\NetworkActivities\NetworkActivityResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

final class EditNetworkActivity extends EditRecord
{
    protected static string $resource = NetworkActivityResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
