<?php

declare(strict_types=1);

namespace App\Filament\Resources\SalesAgents\Tables;

use App\Filament\Concerns\HasStandardExports;
use App\Models\Contact;
use Filament\Actions\BulkActionGroup;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

final class SalesAgentsTable
{
    use HasStandardExports;

    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('updated_at', 'desc')
            ->defaultPaginationPageOption(10)
            ->paginationPageOptions([10, 25, 50])
            ->searchDebounce('300ms')
            ->filters([
                SelectFilter::make('stage')
                    ->options(fn () => Contact::query()->whereNotNull('stage')->where('stage', '!=', '')->distinct()->pluck('stage', 'stage')->all())
                    ->searchable(),
                SelectFilter::make('source_id')
                    ->label('Source')
                    ->relationship('source', 'label')
                    ->searchable()
                    ->preload(),
            ])
            ->columns([
                TextColumn::make('id')->numeric()->sortable(),
                TextColumn::make('first_name')->searchable()->sortable(),
                TextColumn::make('last_name')->searchable()->sortable(),
                TextColumn::make('job_title')->label('Job Title')->searchable()->limit(30)->placeholder('—'),
                TextColumn::make('company_name')->label('Company')->searchable()->limit(30)->placeholder('—'),
                TextColumn::make('type')->badge()->sortable()->placeholder('—'),
                TextColumn::make('stage')->badge()->sortable()->placeholder('—'),
                TextColumn::make('source.label')->label('Source')->sortable()->placeholder('—'),
                TextColumn::make('updated_at')->dateTime()->sortable()->toggleable(isToggledHiddenByDefault: true),
            ])
            ->headerActions([
                self::makeExportHeaderAction('sales-agents'),
            ])
            ->recordActions([])
            ->toolbarActions([
                BulkActionGroup::make([
                    self::makeExportBulkAction(),
                ]),
            ]);
    }
}
