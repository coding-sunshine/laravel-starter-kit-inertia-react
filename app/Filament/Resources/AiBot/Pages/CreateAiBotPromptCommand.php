<?php

declare(strict_types=1);

namespace App\Filament\Resources\AiBot\Pages;

use App\Filament\Resources\AiBot\AiBotPromptCommandResource;
use Filament\Resources\Pages\CreateRecord;

final class CreateAiBotPromptCommand extends CreateRecord
{
    protected static string $resource = AiBotPromptCommandResource::class;
}
