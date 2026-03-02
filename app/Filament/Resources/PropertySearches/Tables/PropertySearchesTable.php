<?php

declare(strict_types=1);

namespace App\Filament\Resources\PropertySearches\Tables;

use App\Filament\Concerns\HasStandardExports;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

final class PropertySearchesTable
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
                TextColumn::make('clientContact.full_name')->label('Client')->searchable(['first_name', 'last_name']),
                TextColumn::make('agentContact.full_name')->label('Agent')->searchable(['first_name', 'last_name']),
                TextColumn::make('preferred_location')->limit(30),
                TextColumn::make('property_type')->badge()->limit(20),
                TextColumn::make('no_of_bedrooms')->numeric()->placeholder('-'),
                TextColumn::make('no_of_bathrooms')->numeric()->placeholder('-'),
                IconColumn::make('preapproval')->boolean(),
                TextColumn::make('created_at')->dateTime()->sortable()->toggleable(isToggledHiddenByDefault: true),
            ])
            ->headerActions([
                self::makeExportHeaderAction('property-searches'),
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
