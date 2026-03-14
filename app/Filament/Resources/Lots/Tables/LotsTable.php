<?php

declare(strict_types=1);

namespace App\Filament\Resources\Lots\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;

final class LotsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->with('project'))
            ->defaultSort('created_at', 'desc')
            ->defaultPaginationPageOption(25)
            ->searchDebounce('300ms')
            ->columns([
                TextColumn::make('title')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('project.title')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('level')
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('bedrooms')
                    ->numeric()
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('bathrooms')
                    ->numeric()
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('car')
                    ->numeric()
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('total')
                    ->numeric()
                    ->sortable()
                    ->label('Total m²')
                    ->toggleable(),
                TextColumn::make('price')
                    ->money('AUD')
                    ->sortable(),
                TextColumn::make('title_status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'available' => 'success',
                        'reserved' => 'warning',
                        'sold' => 'danger',
                        default => 'gray',
                    })
                    ->sortable(),
                IconColumn::make('is_archived')
                    ->boolean()
                    ->toggleable(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('title_status')
                    ->options([
                        'available' => 'Available',
                        'reserved' => 'Reserved',
                        'sold' => 'Sold',
                    ]),
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
