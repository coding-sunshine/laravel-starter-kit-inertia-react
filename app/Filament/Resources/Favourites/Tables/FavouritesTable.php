<?php

declare(strict_types=1);

namespace App\Filament\Resources\Favourites\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\ExportBulkAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

final class FavouritesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('legacy_project_id')
                    ->label('Legacy ID')
                    ->numeric()
                    ->sortable()
                    ->searchable(),
                TextColumn::make('organization.name')
                    ->label('Organization')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('title')
                    ->searchable()
                    ->sortable()
                    ->limit(40),
                TextColumn::make('stage')
                    ->searchable()
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'Planning' => 'gray',
                        'Under Construction' => 'warning',
                        'Ready' => 'success',
                        'Sold Out' => 'danger',
                        default => 'secondary',
                    }),
                TextColumn::make('estate')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('total_lots')
                    ->label('Total Lots')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('min_price')
                    ->label('Min Price')
                    ->money()
                    ->sortable(),
                TextColumn::make('max_price')
                    ->label('Max Price')
                    ->money()
                    ->sortable(),
                TextColumn::make('avg_rent_yield')
                    ->label('Avg Yield %')
                    ->numeric(2)
                    ->sortable()
                    ->suffix('%'),
                IconColumn::make('is_hot_property')
                    ->label('Hot')
                    ->boolean()
                    ->trueIcon('heroicon-o-fire')
                    ->falseIcon('heroicon-o-minus')
                    ->trueColor('danger')
                    ->falseColor('gray'),
                IconColumn::make('is_featured')
                    ->label('Featured')
                    ->boolean()
                    ->trueIcon('heroicon-o-star')
                    ->falseIcon('heroicon-o-minus')
                    ->trueColor('warning')
                    ->falseColor('gray'),
                IconColumn::make('is_smsf')
                    ->label('SMSF')
                    ->boolean(),
                IconColumn::make('is_cashflow_positive')
                    ->label('Cashflow+')
                    ->boolean()
                    ->trueIcon('heroicon-o-arrow-trending-up')
                    ->falseIcon('heroicon-o-arrow-trending-down')
                    ->trueColor('success')
                    ->falseColor('danger'),
                TextColumn::make('developer.name')
                    ->label('Developer')
                    ->searchable()
                    ->sortable()
                    ->limit(20),
                TextColumn::make('projecttype.title')
                    ->label('Type')
                    ->searchable()
                    ->sortable()
                    ->badge(),
                TextColumn::make('created_at')
                    ->label('Added')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Filter::make('active_projects')
                    ->label('Active Only')
                    ->query(fn (Builder $query): Builder => $query->where('is_archived', false))
                    ->default(),
                Filter::make('hot_properties')
                    ->label('Hot Properties')
                    ->query(fn (Builder $query): Builder => $query->where('is_hot_property', true)),
                Filter::make('featured_properties')
                    ->label('Featured Properties')
                    ->query(fn (Builder $query): Builder => $query->where('is_featured', true)),
                Filter::make('smsf_eligible')
                    ->label('SMSF Eligible')
                    ->query(fn (Builder $query): Builder => $query->where('is_smsf', true)),
                Filter::make('cashflow_positive')
                    ->label('Cashflow Positive')
                    ->query(fn (Builder $query): Builder => $query->where('is_cashflow_positive', true)),
            ])
            ->recordActions([
                ViewAction::make()
                    ->url(fn ($record) => "/admin/projects/{$record->id}"),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    ExportBulkAction::make()
                        ->label('Export Favourites'),
                ]),
            ])
            ->defaultSort('updated_at', 'desc')
            ->striped()
            ->paginated([10, 25, 50, 100])
            ->poll('60s') // Auto-refresh every 60 seconds
            ->emptyStateHeading('No favourite properties found')
            ->emptyStateDescription('Properties marked as favourites will appear here.')
            ->emptyStateIcon('heroicon-o-heart');
    }
}
