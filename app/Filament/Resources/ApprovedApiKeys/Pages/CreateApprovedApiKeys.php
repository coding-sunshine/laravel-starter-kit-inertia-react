<?php

declare(strict_types=1);

namespace App\Filament\Resources\ApprovedApiKeys\Pages;

use App\Filament\Resources\ApprovedApiKeys\ApprovedApiKeysResource;
use Filament\Resources\Pages\CreateRecord;

final class CreateApprovedApiKeys extends CreateRecord
{
    protected static string $resource = ApprovedApiKeysResource::class;
}
