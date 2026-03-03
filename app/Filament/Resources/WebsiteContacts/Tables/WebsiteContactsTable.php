<?php

declare(strict_types=1);

namespace App\Filament\Resources\WebsiteContacts\Tables;

use App\Filament\Concerns\HasStandardExports;
use Filament\Actions\BulkActionGroup;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

final class WebsiteContactsTable
{
    use HasStandardExports;

    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->defaultPaginationPageOption(10)
            ->paginationPageOptions([10, 25, 50])
            ->columns([
                TextColumn::make('id')->numeric()->sortable(),
                TextColumn::make('contact.full_name')->label('Contact')->searchable()->placeholder('—'),
                TextColumn::make('source')->searchable()->sortable()->placeholder('—'),
                TextColumn::make('created_at')->dateTime()->sortable(),
            ])
            ->headerActions([
                self::makeExportHeaderAction('website-contacts'),
            ])
            ->recordActions([])
            ->toolbarActions([
                BulkActionGroup::make([
                    self::makeExportBulkAction(),
                ]),
            ]);
    }
}
