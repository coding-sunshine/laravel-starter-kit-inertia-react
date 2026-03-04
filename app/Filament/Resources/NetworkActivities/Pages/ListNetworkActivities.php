<?php

declare(strict_types=1);

namespace App\Filament\Resources\NetworkActivities\Pages;

use App\Filament\Resources\NetworkActivities\NetworkActivityResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

final class ListNetworkActivities extends ListRecords
{
    protected static string $resource = NetworkActivityResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
