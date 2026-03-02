<?php

declare(strict_types=1);

namespace App\Filament\Resources\PropertyReservations\Tables;

use App\Filament\Concerns\HasStandardExports;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

final class PropertyReservationsTable
{
    use HasStandardExports;

    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->defaultPaginationPageOption(10)
            ->paginationPageOptions([10, 25, 50])
            ->searchDebounce('300ms')
            ->columns([
                TextColumn::make('id')->numeric()->sortable(),
                TextColumn::make('primaryContact.full_name')->label('Primary contact')->searchable(['first_name', 'last_name']),
                TextColumn::make('agentContact.full_name')->label('Agent')->searchable(['first_name', 'last_name']),
                TextColumn::make('project.title')->label('Project')->searchable()->limit(30),
                TextColumn::make('lot.id')->label('Lot')->numeric()->sortable(),
                TextColumn::make('purchase_price')->money()->sortable(),
                TextColumn::make('agree_date')->date()->sortable(),
                TextColumn::make('created_at')->dateTime()->sortable()->toggleable(isToggledHiddenByDefault: true),
            ])
            ->headerActions([
                self::makeExportHeaderAction('property-reservations'),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    self::makeExportBulkAction(),
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
