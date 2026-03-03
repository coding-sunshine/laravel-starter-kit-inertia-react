<?php

declare(strict_types=1);

namespace App\Filament\Resources\Developers\Pages;

use App\Filament\Resources\Developers\DeveloperResource;
use Filament\Resources\Pages\ListRecords;

final class ListDevelopers extends ListRecords
{
    protected static string $resource = DeveloperResource::class;
}
