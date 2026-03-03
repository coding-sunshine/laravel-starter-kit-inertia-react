<?php

declare(strict_types=1);

namespace App\Filament\Resources\SprRequests\Pages;

use App\Filament\Resources\SprRequests\SprRequestResource;
use Filament\Resources\Pages\ListRecords;

final class ListSprRequests extends ListRecords
{
    protected static string $resource = SprRequestResource::class;
}
