<?php

declare(strict_types=1);

namespace App\Filament\Resources\WebsiteContacts\Pages;

use App\Filament\Resources\WebsiteContacts\WebsiteContactResource;
use Filament\Resources\Pages\ListRecords;

final class ListWebsiteContacts extends ListRecords
{
    protected static string $resource = WebsiteContactResource::class;
}
