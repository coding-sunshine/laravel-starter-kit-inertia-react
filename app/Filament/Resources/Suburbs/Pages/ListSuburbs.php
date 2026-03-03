<?php

declare(strict_types=1);

namespace App\Filament\Resources\Suburbs\Pages;

use App\Filament\Resources\Suburbs\SuburbResource;
use Filament\Resources\Pages\ListRecords;

final class ListSuburbs extends ListRecords
{
    protected static string $resource = SuburbResource::class;
}
