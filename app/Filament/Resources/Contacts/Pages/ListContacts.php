<?php

declare(strict_types=1);

namespace App\Filament\Resources\Contacts\Pages;

use App\Filament\Resources\Contacts\ContactResource;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Contracts\Support\Htmlable;

final class ListContacts extends ListRecords
{
    protected static string $resource = ContactResource::class;

    public function getTitle(): string|Htmlable
    {
        return 'Contacts / Leads report';
    }

    public function getHeaderWidgets(): array
    {
        return [
            \App\Filament\Resources\Contacts\Widgets\ContactsFunnelWidget::class,
        ];
    }

    public function getHeaderWidgetsColumns(): int|array
    {
        return 1;
    }
}
