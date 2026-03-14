<?php

declare(strict_types=1);

namespace App\Filament\Resources\Projects\Tables;

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

final class ProjectsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->with(['developer', 'projecttype']))
            ->defaultSort('created_at', 'desc')
            ->defaultPaginationPageOption(25)
            ->searchDebounce('300ms')
            ->columns([
                TextColumn::make('title')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('suburb')
                    ->searchable()
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('state')
                    ->sortable()
                    ->badge()
                    ->toggleable(),
                TextColumn::make('developer.name')
                    ->searchable()
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('stage')
                    ->badge()
                    ->sortable(),
                TextColumn::make('min_price')
                    ->money('AUD')
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('total_lots')
                    ->numeric()
                    ->sortable()
                    ->toggleable(),
                IconColumn::make('is_hot_property')
                    ->boolean()
                    ->toggleable(),
                IconColumn::make('is_featured')
                    ->boolean()
                    ->toggleable(),
                IconColumn::make('is_archived')
                    ->boolean()
                    ->toggleable(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('stage')
                    ->options([
                        'pre_launch' => 'Pre-Launch',
                        'selling' => 'Selling',
                        'completed' => 'Completed',
                        'archived' => 'Archived',
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
