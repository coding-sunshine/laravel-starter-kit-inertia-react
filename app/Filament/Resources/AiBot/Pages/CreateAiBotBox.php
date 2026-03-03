<?php

declare(strict_types=1);

namespace App\Filament\Resources\AiBot\Pages;

use App\Filament\Resources\AiBot\AiBotBoxResource;
use Filament\Resources\Pages\CreateRecord;

final class CreateAiBotBox extends CreateRecord
{
    protected static string $resource = AiBotBoxResource::class;
}
