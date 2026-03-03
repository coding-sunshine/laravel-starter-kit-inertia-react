<?php

declare(strict_types=1);

namespace App\Filament\Resources\AiBot\Pages;

use App\Filament\Resources\AiBot\AiBotCategoryResource;
use Filament\Resources\Pages\CreateRecord;

final class CreateAiBotCategory extends CreateRecord
{
    protected static string $resource = AiBotCategoryResource::class;
}
