<?php

declare(strict_types=1);

namespace App\Filament\Resources\ProjectUpdates\Pages;

use App\Filament\Resources\ProjectUpdates\ProjectUpdateResource;
use Filament\Resources\Pages\ListRecords;

final class ListProjectUpdates extends ListRecords
{
    protected static string $resource = ProjectUpdateResource::class;
}
