<?php

declare(strict_types=1);

namespace App\Filament\Resources\PenaltyReconciliations\Pages;

use App\Filament\Resources\PenaltyReconciliations\PenaltyReconciliationResource;
use Filament\Resources\Pages\ListRecords;

final class ListPenaltyReconciliations extends ListRecords
{
    protected static string $resource = PenaltyReconciliationResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
