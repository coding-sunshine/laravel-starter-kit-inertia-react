<?php

declare(strict_types=1);

namespace App\Filament\Resources\PotentialProperties\Pages;

use App\Filament\Resources\PotentialProperties\PotentialPropertyResource;
use Filament\Resources\Pages\ListRecords;

final class ListPotentialProperties extends ListRecords
{
    protected static string $resource = PotentialPropertyResource::class;
}
