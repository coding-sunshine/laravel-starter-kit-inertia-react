<?php

declare(strict_types=1);

namespace App\Filament\Resources\States\Pages;

use App\Filament\Resources\States\StateResource;
use Filament\Resources\Pages\ListRecords;

final class ListStates extends ListRecords
{
    protected static string $resource = StateResource::class;
}
