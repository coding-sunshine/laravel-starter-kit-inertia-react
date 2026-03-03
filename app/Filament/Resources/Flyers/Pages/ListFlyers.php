<?php

declare(strict_types=1);

namespace App\Filament\Resources\Flyers\Pages;

use App\Filament\Resources\Flyers\FlyerResource;
use Filament\Resources\Pages\ListRecords;

final class ListFlyers extends ListRecords
{
    protected static string $resource = FlyerResource::class;
}
