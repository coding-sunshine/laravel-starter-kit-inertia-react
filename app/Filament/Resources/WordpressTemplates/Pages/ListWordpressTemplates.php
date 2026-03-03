<?php

declare(strict_types=1);

namespace App\Filament\Resources\WordpressTemplates\Pages;

use App\Filament\Resources\WordpressTemplates\WordpressTemplateResource;
use Filament\Resources\Pages\ListRecords;

final class ListWordpressTemplates extends ListRecords
{
    protected static string $resource = WordpressTemplateResource::class;
}
