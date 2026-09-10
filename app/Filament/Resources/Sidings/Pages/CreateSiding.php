<?php

declare(strict_types=1);

namespace App\Filament\Resources\Sidings\Pages;

use App\Filament\Resources\Sidings\SidingResource;
use Filament\Resources\Pages\CreateRecord;

final class CreateSiding extends CreateRecord
{
    protected static string $resource = SidingResource::class;
}
