<?php

declare(strict_types=1);

namespace App\Filament\Resources\TermsVersions\Tables;

use App\Enums\TermsType;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

final class TermsVersionsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('title')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('slug')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('type')
                    ->badge()
                    ->formatStateUsing(fn (TermsType $state): string => $state->label()),
                TextColumn::make('effective_at')
                    ->date()
                    ->sortable(),
                IconColumn::make('is_required')
                    ->boolean()
                    ->label('Required'),
                TextColumn::make('acceptances_count')
                    ->counts('acceptances')
                    ->label('Acceptances')
                    ->sortable(),
            ])
            ->defaultSort('effective_at', 'desc')
            ->filters([
                //
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
