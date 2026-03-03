<?php

declare(strict_types=1);

namespace App\Filament\Resources\Commissions\Tables;

use App\Filament\Concerns\HasStandardExports;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

final class CommissionsTable
{
    use HasStandardExports;

    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->defaultPaginationPageOption(10)
            ->paginationPageOptions([10, 25, 50])
            ->filters([
                Filter::make('created_at')
                    ->form([
                        DatePicker::make('created_from')->label('From date'),
                        DatePicker::make('created_until')->label('To date'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when($data['created_from'] ?? null, fn (Builder $q) => $q->whereDate('created_at', '>=', $data['created_from']))
                            ->when($data['created_until'] ?? null, fn (Builder $q) => $q->whereDate('created_at', '<=', $data['created_until']));
                    }),
            ])
            ->columns([
                TextColumn::make('id')->numeric()->sortable(),
                TextColumn::make('commissionable_type')->label('Type')->limit(30),
                TextColumn::make('commissionable_id')->label('Related ID')->numeric()->sortable(),
                TextColumn::make('commission_in')->money()->sortable()->placeholder('-'),
                TextColumn::make('commission_out')->money()->sortable()->placeholder('-'),
                TextColumn::make('commission_profit')->money()->sortable()->placeholder('-'),
                TextColumn::make('commission_percent_in')->label('% In')->placeholder('-')->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('commission_percent_out')->label('% Out')->placeholder('-')->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('commission_percent_profit')->label('% Profit')->placeholder('-')->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('created_at')->dateTime()->sortable()->toggleable(isToggledHiddenByDefault: true),
            ])
            ->headerActions([
                self::makeExportHeaderAction('commissions'),
            ])
            ->recordActions([
                ViewAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    self::makeExportBulkAction(),
                ]),
            ]);
    }
}
