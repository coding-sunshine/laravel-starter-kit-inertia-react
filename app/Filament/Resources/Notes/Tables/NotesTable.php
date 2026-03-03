<?php

declare(strict_types=1);

namespace App\Filament\Resources\Notes\Tables;

use App\Filament\Concerns\HasStandardExports;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

final class NotesTable
{
    use HasStandardExports;

    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->defaultPaginationPageOption(10)
            ->paginationPageOptions([10, 25, 50])
            ->filters([
                SelectFilter::make('type')
                    ->options(fn () => \App\Models\Note::query()->distinct()->pluck('type', 'type')->filter()->all())
                    ->searchable(),
                SelectFilter::make('noteable_type')
                    ->label('Related type')
                    ->options([
                        'App\\Models\\Contact' => 'Contact',
                        'App\\Models\\Sale' => 'Sale',
                        'App\\Models\\Project' => 'Project',
                    ]),
            ])
            ->columns([
                TextColumn::make('id')->numeric()->sortable(),
                TextColumn::make('noteable_type')->label('Related type')->limit(30),
                TextColumn::make('content')->limit(50)->searchable(),
                TextColumn::make('type')->placeholder('-'),
                TextColumn::make('author.name')->label('Author')->placeholder('-'),
                TextColumn::make('created_at')->dateTime()->sortable(),
            ])
            ->headerActions([
                self::makeExportHeaderAction('notes'),
            ])
            ->recordActions([
                ViewAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    self::makeExportBulkAction(),
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
