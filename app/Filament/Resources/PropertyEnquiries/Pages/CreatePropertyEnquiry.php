<?php

declare(strict_types=1);

namespace App\Filament\Resources\PropertyEnquiries\Pages;

use App\Filament\Resources\PropertyEnquiries\PropertyEnquiryResource;
use Filament\Resources\Pages\CreateRecord;

final class CreatePropertyEnquiry extends CreateRecord
{
    protected static string $resource = PropertyEnquiryResource::class;
}
