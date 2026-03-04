<?php

declare(strict_types=1);

namespace App\Filament\Resources\ReferralPartners\Pages;

use App\Filament\Resources\ReferralPartners\ReferralPartnerResource;
use Filament\Resources\Pages\ListRecords;

final class ListReferralPartners extends ListRecords
{
    protected static string $resource = ReferralPartnerResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
