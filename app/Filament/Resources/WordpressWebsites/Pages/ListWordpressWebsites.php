<?php

declare(strict_types=1);

namespace App\Filament\Resources\WordpressWebsites\Pages;

use App\Filament\Resources\WordpressWebsites\WordpressWebsiteResource;
use Filament\Resources\Pages\ListRecords;

final class ListWordpressWebsites extends ListRecords
{
    protected static string $resource = WordpressWebsiteResource::class;
}
