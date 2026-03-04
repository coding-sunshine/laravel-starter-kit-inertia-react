<?php

declare(strict_types=1);

namespace App\Filament\Resources\LogInHistories\Pages;

use App\Filament\Resources\LogInHistories\LogInHistoryResource;
use Filament\Resources\Pages\CreateRecord;

final class CreateLogInHistory extends CreateRecord
{
    protected static string $resource = LogInHistoryResource::class;
}
