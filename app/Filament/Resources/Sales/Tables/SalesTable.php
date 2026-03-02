<?php

declare(strict_types=1);

namespace App\Filament\Resources\Sales\Tables;

use App\Filament\Concerns\HasStandardExports;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

final class SalesTable
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
                TextColumn::make('project.title')->label('Project')->searchable()->limit(30),
                TextColumn::make('lot.id')->label('Lot')->numeric()->sortable(),
                TextColumn::make('developer.legacy_developer_id')->label('Developer')->numeric()->placeholder('-'),
                TextColumn::make('comms_in_total')->money()->sortable()->placeholder('-'),
                TextColumn::make('comms_out_total')->money()->sortable()->placeholder('-'),
                TextColumn::make('finance_due_date')->date()->sortable()->placeholder('-'),
                IconColumn::make('is_comments_enabled')->boolean()->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('status_updated_at')->dateTime()->sortable()->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('created_at')->dateTime()->sortable()->toggleable(isToggledHiddenByDefault: true),
            ])
            ->headerActions([
                self::makeExportHeaderAction('sales'),
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
