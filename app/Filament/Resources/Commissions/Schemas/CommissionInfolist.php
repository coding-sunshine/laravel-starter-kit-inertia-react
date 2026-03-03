<?php

declare(strict_types=1);

namespace App\Filament\Resources\Commissions\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

final class CommissionInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('id')->label('ID'),
                TextEntry::make('commissionable_type')->label('Type'),
                TextEntry::make('commissionable_id')->label('Related ID'),
                TextEntry::make('commission_in')->money()->placeholder('-'),
                TextEntry::make('commission_out')->money()->placeholder('-'),
                TextEntry::make('commission_profit')->money()->placeholder('-'),
                TextEntry::make('commission_percent_in')->placeholder('-'),
                TextEntry::make('commission_percent_out')->placeholder('-'),
                TextEntry::make('commission_percent_profit')->placeholder('-'),
                TextEntry::make('created_at')->dateTime(),
                TextEntry::make('updated_at')->dateTime(),
            ]);
    }
}
