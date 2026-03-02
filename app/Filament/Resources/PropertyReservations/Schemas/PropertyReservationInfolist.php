<?php

declare(strict_types=1);

namespace App\Filament\Resources\PropertyReservations\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

final class PropertyReservationInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('primaryContact.full_name')->label('Primary contact'),
                TextEntry::make('agentContact.full_name')->label('Agent'),
                TextEntry::make('secondaryContact.full_name')->label('Secondary contact')->placeholder('-'),
                TextEntry::make('project.title')->label('Project'),
                TextEntry::make('lot.id')->label('Lot'),
                TextEntry::make('purchase_price')->money(),
                TextEntry::make('purchaser_type')->badge()->placeholder('-'),
                TextEntry::make('agree_date')->date()->placeholder('-'),
                TextEntry::make('created_at')->dateTime(),
                TextEntry::make('updated_at')->dateTime(),
            ]);
    }
}
