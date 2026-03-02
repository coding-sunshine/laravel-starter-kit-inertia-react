<?php

namespace App\Filament\Resources\Lots\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;

class LotsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('legacy_lot_id')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('project.title')
                    ->searchable(),
                TextColumn::make('title')
                    ->searchable(),
                TextColumn::make('land_price')
                    ->money()
                    ->sortable(),
                TextColumn::make('build_price')
                    ->money()
                    ->sortable(),
                TextColumn::make('stage')
                    ->searchable(),
                TextColumn::make('level')
                    ->searchable(),
                TextColumn::make('building')
                    ->searchable(),
                TextColumn::make('floorplan')
                    ->searchable(),
                TextColumn::make('car')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('storage')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('view')
                    ->searchable(),
                TextColumn::make('garage')
                    ->searchable(),
                TextColumn::make('aspect')
                    ->searchable(),
                TextColumn::make('internal')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('external')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('total')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('storyes')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('land_size')
                    ->searchable(),
                TextColumn::make('title_status')
                    ->searchable(),
                TextColumn::make('living_area')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('price')
                    ->money()
                    ->sortable(),
                TextColumn::make('bedrooms')
                    ->searchable(),
                TextColumn::make('bathrooms')
                    ->searchable(),
                TextColumn::make('study')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('mpr')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('powder_room')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('balcony')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('rent_yield')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('weekly_rent')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('rent_to_sell_yield')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('rates')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('five_percent_share_price')
                    ->money()
                    ->sortable(),
                TextColumn::make('sub_agent_comms')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('body_corporation')
                    ->numeric()
                    ->sortable(),
                IconColumn::make('is_archived')
                    ->boolean(),
                IconColumn::make('is_nras')
                    ->boolean(),
                IconColumn::make('is_smsf')
                    ->boolean(),
                IconColumn::make('is_cashflow_positive')
                    ->boolean(),
                TextColumn::make('completion')
                    ->searchable(),
                TextColumn::make('uuid')
                    ->label('UUID')
                    ->searchable(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('deleted_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('created_by')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('updated_by')
                    ->numeric()
                    ->sortable(),
            ])
            ->filters([
                TrashedFilter::make(),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    ForceDeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                ]),
            ]);
    }
}
