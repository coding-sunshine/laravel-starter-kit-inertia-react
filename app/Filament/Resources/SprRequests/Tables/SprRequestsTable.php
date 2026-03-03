<?php

declare(strict_types=1);

namespace App\Filament\Resources\SprRequests\Tables;

use App\Filament\Concerns\HasStandardExports;
use Filament\Actions\BulkActionGroup;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

final class SprRequestsTable
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
                TextColumn::make('user.name')->label('User')->placeholder('—'),
                TextColumn::make('spr_count')->numeric()->sortable()->placeholder('—'),
                TextColumn::make('spr_price')->money()->sortable()->placeholder('—'),
                IconColumn::make('is_request_completed')->boolean()->label('Completed')->sortable(),
                TextColumn::make('created_at')->dateTime()->sortable(),
            ])
            ->headerActions([
                self::makeExportHeaderAction('spr-requests'),
            ])
            ->recordActions([])
            ->toolbarActions([
                BulkActionGroup::make([
                    self::makeExportBulkAction(),
                ]),
            ]);
    }
}
