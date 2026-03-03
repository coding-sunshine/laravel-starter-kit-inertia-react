<?php

declare(strict_types=1);

namespace App\Filament\Resources\Websites\Tables;

use App\Filament\Concerns\HasStandardExports;
use Filament\Actions\BulkActionGroup;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

final class WebsitesTable
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
                TextColumn::make('domain')->searchable()->sortable()->placeholder('—'),
                TextColumn::make('created_at')->dateTime()->sortable(),
            ])
            ->headerActions([
                self::makeExportHeaderAction('websites'),
            ])
            ->recordActions([])
            ->toolbarActions([
                BulkActionGroup::make([
                    self::makeExportBulkAction(),
                ]),
            ]);
    }
}
