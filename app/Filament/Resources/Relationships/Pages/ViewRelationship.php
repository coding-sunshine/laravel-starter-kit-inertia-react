<?php

declare(strict_types=1);

namespace App\Filament\Resources\Relationships\Pages;

use App\Filament\Resources\Relationships\RelationshipResource;
use Filament\Resources\Pages\ViewRecord;

final class ViewRelationship extends ViewRecord
{
    protected static string $resource = RelationshipResource::class;
}
