<?php

declare(strict_types=1);

namespace App\Filament\Resources\PropertyEnquiries\Pages;

use App\Filament\Resources\PropertyEnquiries\PropertyEnquiryResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

final class ViewPropertyEnquiry extends ViewRecord
{
    protected static string $resource = PropertyEnquiryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
