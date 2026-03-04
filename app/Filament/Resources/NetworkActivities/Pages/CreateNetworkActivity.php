<?php

declare(strict_types=1);

namespace App\Filament\Resources\NetworkActivities\Pages;

use App\Filament\Resources\NetworkActivities\NetworkActivityResource;
use Filament\Resources\Pages\CreateRecord;

final class CreateNetworkActivity extends CreateRecord
{
    protected static string $resource = NetworkActivityResource::class;
}
