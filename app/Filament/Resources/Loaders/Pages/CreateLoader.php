<?php

declare(strict_types=1);

namespace App\Filament\Resources\Loaders\Pages;

use App\Filament\Resources\Loaders\LoaderResource;
use Filament\Resources\Pages\CreateRecord;

final class CreateLoader extends CreateRecord
{
    protected static string $resource = LoaderResource::class;
}
