<?php

declare(strict_types=1);

namespace App\Filament\Resources\Notes\Pages;

use App\Filament\Resources\Notes\NoteResource;
use Filament\Resources\Pages\CreateRecord;

final class CreateNote extends CreateRecord
{
    protected static string $resource = NoteResource::class;
}
