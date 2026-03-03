<?php

declare(strict_types=1);

namespace App\Filament\Resources\Tasks\Tables;

use App\Filament\Concerns\HasStandardExports;
use Filament\Actions\BulkActionGroup;
use Filament\Forms\Components\DatePicker;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

final class TasksTable
{
    use HasStandardExports;

    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('due_at', 'desc')
            ->defaultPaginationPageOption(10)
            ->paginationPageOptions([10, 25, 50])
            ->searchDebounce('300ms')
            ->filters([
                Filter::make('due_at')
                    ->form([
                        DatePicker::make('due_from')->label('Due from'),
                        DatePicker::make('due_until')->label('Due until'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when($data['due_from'] ?? null, fn (Builder $q) => $q->whereDate('due_at', '>=', $data['due_from']))
                            ->when($data['due_until'] ?? null, fn (Builder $q) => $q->whereDate('due_at', '<=', $data['due_until']));
                    })
                    ->indicateUsing(function (array $data): array {
                        $indicators = [];
                        if ($data['due_from'] ?? null) {
                            $indicators[] = \Filament\Tables\Filters\Indicator::make('Due from '.$data['due_from'])->removeField('due_from');
                        }
                        if ($data['due_until'] ?? null) {
                            $indicators[] = \Filament\Tables\Filters\Indicator::make('Due until '.$data['due_until'])->removeField('due_until');
                        }
                        return $indicators;
                    }),
                SelectFilter::make('status')
                    ->options([
                        'open' => 'Open',
                        'in_progress' => 'In progress',
                        'completed' => 'Completed',
                        'cancelled' => 'Cancelled',
                    ])
                    ->nullable(),
                SelectFilter::make('assigned_to_user_id')
                    ->label('Assigned to')
                    ->relationship('assignedUser', 'name')
                    ->searchable()
                    ->preload(),
                SelectFilter::make('assigned_contact_id')
                    ->label('Assigned contact')
                    ->relationship('assignedContact', 'first_name', modifyQueryUsing: fn ($q) => $q->orderBy('first_name'))
                    ->getOptionLabelFromRecordUsing(fn ($record) => trim($record->first_name.' '.$record->last_name))
                    ->searchable()
                    ->preload(),
            ])
            ->columns([
                TextColumn::make('id')->numeric()->sortable(),
                TextColumn::make('title')->searchable()->sortable()->limit(40),
                TextColumn::make('status')->badge()->sortable(),
                TextColumn::make('priority')->badge()->sortable()->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('due_at')->date()->sortable()->placeholder('—'),
                TextColumn::make('assignedContact.full_name')->label('Assigned contact')->sortable()->placeholder('—'),
                TextColumn::make('assignedUser.name')->label('Assigned to')->sortable()->placeholder('—'),
                IconColumn::make('completed_at')->boolean()->label('Done')->sortable(),
                TextColumn::make('created_at')->dateTime()->sortable()->toggleable(isToggledHiddenByDefault: true),
            ])
            ->headerActions([
                self::makeExportHeaderAction('tasks'),
            ])
            ->recordActions([])
            ->toolbarActions([
                BulkActionGroup::make([
                    self::makeExportBulkAction(),
                ]),
            ]);
    }
}
