<?php

declare(strict_types=1);

namespace App\Filament\Resources\SalesAgents\Pages;

use App\Filament\Resources\SalesAgents\SalesAgentResource;
use Filament\Resources\Pages\ListRecords;

final class ListSalesAgents extends ListRecords
{
    protected static string $resource = SalesAgentResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
