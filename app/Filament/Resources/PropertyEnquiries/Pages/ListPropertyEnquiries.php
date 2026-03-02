<?php

declare(strict_types=1);

namespace App\Filament\Resources\PropertyEnquiries\Pages;

use App\Filament\Resources\PropertyEnquiries\PropertyEnquiryResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

final class ListPropertyEnquiries extends ListRecords
{
    protected static string $resource = PropertyEnquiryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
