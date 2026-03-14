<?php

declare(strict_types=1);

namespace App\Filament\Resources\CampaignWebsites\Pages;

use App\Filament\Resources\CampaignWebsites\CampaignWebsiteResource;
use Filament\Resources\Pages\CreateRecord;

final class CreateCampaignWebsite extends CreateRecord
{
    protected static string $resource = CampaignWebsiteResource::class;
}
