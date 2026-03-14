<?php

declare(strict_types=1);

namespace App\Filament\Resources\PropertyEnquiries\Pages;

use App\Filament\Resources\PropertyEnquiries\PropertyEnquiryResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

final class EditPropertyEnquiry extends EditRecord
{
    protected static string $resource = PropertyEnquiryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
