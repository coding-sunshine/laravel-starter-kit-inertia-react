<?php

declare(strict_types=1);

namespace App\Filament\Resources\FlyerTemplates\Pages;

use App\Filament\Resources\FlyerTemplates\FlyerTemplateResource;
use Filament\Resources\Pages\ListRecords;

final class ListFlyerTemplates extends ListRecords
{
    protected static string $resource = FlyerTemplateResource::class;
}
