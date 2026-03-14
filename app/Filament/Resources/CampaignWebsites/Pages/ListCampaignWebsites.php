<?php

declare(strict_types=1);

namespace App\Filament\Resources\CampaignWebsites\Pages;

use App\Filament\Resources\CampaignWebsites\CampaignWebsiteResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

final class ListCampaignWebsites extends ListRecords
{
    protected static string $resource = CampaignWebsiteResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
