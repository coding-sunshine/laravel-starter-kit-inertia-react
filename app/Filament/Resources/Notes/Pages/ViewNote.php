<?php

declare(strict_types=1);

namespace App\Filament\Resources\Notes\Pages;

use App\Filament\Resources\Notes\NoteResource;
use Filament\Resources\Pages\ViewRecord;

final class ViewNote extends ViewRecord
{
    protected static string $resource = NoteResource::class;
}
