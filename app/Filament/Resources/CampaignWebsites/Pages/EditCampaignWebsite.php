<?php

declare(strict_types=1);

namespace App\Filament\Resources\CampaignWebsites\Pages;

use App\Filament\Resources\CampaignWebsites\CampaignWebsiteResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Resources\Pages\EditRecord;

final class EditCampaignWebsite extends EditRecord
{
    protected static string $resource = CampaignWebsiteResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
            ForceDeleteAction::make(),
            RestoreAction::make(),
        ];
    }
}
