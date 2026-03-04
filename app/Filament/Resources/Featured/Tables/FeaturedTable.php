<?php

declare(strict_types=1);

namespace App\Filament\Resources\Featured\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\ExportBulkAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

final class FeaturedTable
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
                    ->limit(30),
                TextColumn::make('stage')
                    ->searchable()
                    ->sortable(),
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
                TextColumn::make('avg_price')
                    ->label('Avg Price')
                    ->money()
                    ->sortable(),
                TextColumn::make('min_rent_yield')
                    ->label('Min Yield %')
                    ->numeric(2)
                    ->sortable()
                    ->suffix('%'),
                TextColumn::make('max_rent_yield')
                    ->label('Max Yield %')
                    ->numeric(2)
                    ->sortable()
                    ->suffix('%'),
                IconColumn::make('is_hot_property')
                    ->label('Hot Property')
                    ->boolean(),
                IconColumn::make('is_smsf')
                    ->label('SMSF')
                    ->boolean(),
                IconColumn::make('is_cashflow_positive')
                    ->label('Cashflow+')
                    ->boolean(),
                TextColumn::make('developer.name')
                    ->label('Developer')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('projecttype.title')
                    ->label('Type')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->label('Updated')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Filter::make('active_projects')
                    ->label('Active Projects')
                    ->query(fn (Builder $query): Builder => $query->where('is_archived', false))
                    ->default(),
                Filter::make('hot_properties')
                    ->label('Hot Properties')
                    ->query(fn (Builder $query): Builder => $query->where('is_hot_property', true)),
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
                        ->label('Export to Excel'),
                ]),
            ])
            ->defaultSort('updated_at', 'desc')
            ->striped()
            ->paginated([10, 25, 50, 100]);
    }
}
