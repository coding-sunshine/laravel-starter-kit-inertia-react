<?php

declare(strict_types=1);

namespace App\Filament\Resources\Vouchers\Tables;

use BeyondCode\Vouchers\Models\Voucher;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

final class VouchersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('code')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('data.discount_type')
                    ->label('Type')
                    ->formatStateUsing(fn (?string $state): string => $state === 'fixed' ? 'Fixed' : 'Percentage'),

                TextColumn::make('data.discount_amount')
                    ->label('Amount')
                    ->suffix(fn (Voucher $record): string => ($record->data['discount_type'] ?? '') === 'percentage' ? '%' : ''),

                TextColumn::make('expires_at')
                    ->date()
                    ->sortable()
                    ->placeholder('Never'),

                TextColumn::make('users_count')
                    ->label('Redeemed')
                    ->counts('users'),
            ])
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
