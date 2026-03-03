<?php

declare(strict_types=1);

namespace App\Filament\Resources\Partners\Pages;

use App\Filament\Resources\Partners\PartnerResource;
use Filament\Resources\Pages\CreateRecord;

final class CreatePartner extends CreateRecord
{
    protected static string $resource = PartnerResource::class;
}
