<?php

declare(strict_types=1);

namespace App\Filament\Resources\Lots\Schemas;

use App\Models\Lot;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

final class LotInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('project.title')
                    ->label('Project'),
                TextEntry::make('legacy_id')
                    ->numeric()
                    ->placeholder('-'),
                TextEntry::make('slug')
                    ->placeholder('-'),
                TextEntry::make('title')
                    ->placeholder('-'),
                TextEntry::make('land_price')
                    ->money()
                    ->placeholder('-'),
                TextEntry::make('build_price')
                    ->money()
                    ->placeholder('-'),
                TextEntry::make('stage')
                    ->placeholder('-'),
                TextEntry::make('level')
                    ->placeholder('-'),
                TextEntry::make('building')
                    ->placeholder('-'),
                TextEntry::make('floorplan')
                    ->placeholder('-'),
                TextEntry::make('car')
                    ->numeric()
                    ->placeholder('-'),
                TextEntry::make('storage')
                    ->placeholder('-'),
                TextEntry::make('view')
                    ->placeholder('-'),
                TextEntry::make('garage')
                    ->numeric()
                    ->placeholder('-'),
                TextEntry::make('aspect')
                    ->placeholder('-'),
                TextEntry::make('internal')
                    ->numeric()
                    ->placeholder('-'),
                TextEntry::make('external')
                    ->numeric()
                    ->placeholder('-'),
                TextEntry::make('total')
                    ->numeric()
                    ->placeholder('-'),
                TextEntry::make('storeys')
                    ->numeric()
                    ->placeholder('-'),
                TextEntry::make('land_size')
                    ->numeric()
                    ->placeholder('-'),
                TextEntry::make('title_status'),
                TextEntry::make('living_area')
                    ->numeric()
                    ->placeholder('-'),
                TextEntry::make('price')
                    ->money()
                    ->placeholder('-'),
                TextEntry::make('bedrooms')
                    ->numeric()
                    ->placeholder('-'),
                TextEntry::make('bathrooms')
                    ->numeric()
                    ->placeholder('-'),
                TextEntry::make('study')
                    ->numeric()
                    ->placeholder('-'),
                IconEntry::make('mpr')
                    ->boolean(),
                IconEntry::make('powder_room')
                    ->boolean(),
                TextEntry::make('balcony')
                    ->numeric()
                    ->placeholder('-'),
                TextEntry::make('rent_yield')
                    ->numeric()
                    ->placeholder('-'),
                TextEntry::make('weekly_rent')
                    ->numeric()
                    ->placeholder('-'),
                TextEntry::make('rates')
                    ->numeric()
                    ->placeholder('-'),
                TextEntry::make('body_corporation')
                    ->numeric()
                    ->placeholder('-'),
                IconEntry::make('is_archived')
                    ->boolean(),
                IconEntry::make('is_nras')
                    ->boolean(),
                IconEntry::make('is_smsf')
                    ->boolean(),
                IconEntry::make('is_cashflow_positive')
                    ->boolean(),
                TextEntry::make('completion')
                    ->date()
                    ->placeholder('-'),
                TextEntry::make('created_by')
                    ->numeric()
                    ->placeholder('-'),
                TextEntry::make('updated_by')
                    ->numeric()
                    ->placeholder('-'),
                TextEntry::make('created_at')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('updated_at')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('deleted_at')
                    ->dateTime()
                    ->visible(fn (Lot $record): bool => $record->trashed()),
            ]);
    }
}
