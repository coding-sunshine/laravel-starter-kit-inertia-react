<?php

declare(strict_types=1);

namespace App\Filament\Resources\EnterpriseInquiries\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

final class EnterpriseInquiryInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('name'),
                TextEntry::make('email'),
                TextEntry::make('company'),
                TextEntry::make('phone'),
                TextEntry::make('status')->badge(),
                TextEntry::make('created_at')->dateTime(),
                TextEntry::make('message')->columnSpanFull(),
            ]);
    }
}
