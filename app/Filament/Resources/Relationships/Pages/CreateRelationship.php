<?php

declare(strict_types=1);

namespace App\Filament\Resources\Relationships\Pages;

use App\Filament\Resources\Relationships\RelationshipResource;
use Filament\Resources\Pages\CreateRecord;

final class CreateRelationship extends CreateRecord
{
    protected static string $resource = RelationshipResource::class;
}
