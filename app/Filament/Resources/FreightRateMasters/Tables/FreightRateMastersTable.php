<?php

declare(strict_types=1);

namespace App\Filament\Resources\FreightRateMasters\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;

final class FreightRateMastersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('commodity_code')
                    ->searchable(),
                TextColumn::make('commodity_name')
                    ->searchable(),
                TextColumn::make('class_code')
                    ->searchable(),
                TextColumn::make('risk_rate')
                    ->searchable(),
                TextColumn::make('distance_from_km')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('distance_to_km')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('rate_per_mt')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('gst_percent')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('effective_from')
                    ->date()
                    ->sortable(),
                TextColumn::make('effective_to')
                    ->date()
                    ->sortable(),
                IconColumn::make('is_active')
                    ->boolean(),
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
            ])
            ->filters([
                TrashedFilter::make(),
            ])
            ->recordActions([
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
