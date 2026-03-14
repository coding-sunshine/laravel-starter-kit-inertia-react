<?php

declare(strict_types=1);

namespace App\Filament\Resources\PropertySearches\Pages;

use App\Filament\Resources\PropertySearches\PropertySearchResource;
use Filament\Resources\Pages\CreateRecord;

final class CreatePropertySearch extends CreateRecord
{
    protected static string $resource = PropertySearchResource::class;
}
