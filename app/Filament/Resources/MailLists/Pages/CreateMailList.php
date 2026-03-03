<?php

declare(strict_types=1);

namespace App\Filament\Resources\MailLists\Pages;

use App\Filament\Resources\MailLists\MailListResource;
use Filament\Resources\Pages\CreateRecord;

final class CreateMailList extends CreateRecord
{
    protected static string $resource = MailListResource::class;
}
