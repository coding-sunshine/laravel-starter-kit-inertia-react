<?php

declare(strict_types=1);

namespace App\Filament\Resources\WordpressWebsites\Tables;

use App\Filament\Concerns\HasStandardExports;
use Filament\Actions\BulkActionGroup;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

final class WordpressWebsitesTable
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
                TextColumn::make('name')->searchable()->sortable(),
                TextColumn::make('url')->searchable()->sortable()->placeholder('—'),
                TextColumn::make('created_at')->dateTime()->sortable(),
            ])
            ->headerActions([
                self::makeExportHeaderAction('wordpress-websites'),
            ])
            ->recordActions([])
            ->toolbarActions([
                BulkActionGroup::make([
                    self::makeExportBulkAction(),
                ]),
            ]);
    }
}
