<?php

declare(strict_types=1);

namespace App\Filament\Resources\Flyers\Tables;

use App\Filament\Concerns\HasStandardExports;
use Filament\Actions\BulkActionGroup;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

final class FlyersTable
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
                TextColumn::make('project.title')->label('Project')->searchable()->placeholder('—'),
                TextColumn::make('lot.title')->label('Lot')->placeholder('—'),
                TextColumn::make('flyerTemplate.name')->label('Template')->placeholder('—'),
                IconColumn::make('is_custom')->boolean()->label('Custom')->sortable(),
                TextColumn::make('created_at')->dateTime()->sortable(),
            ])
            ->headerActions([
                self::makeExportHeaderAction('flyers'),
            ])
            ->recordActions([])
            ->toolbarActions([
                BulkActionGroup::make([
                    self::makeExportBulkAction(),
                ]),
            ]);
    }
}
