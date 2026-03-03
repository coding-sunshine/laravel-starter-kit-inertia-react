<?php

declare(strict_types=1);

namespace App\Filament\Resources\Sales\Tables;

use App\Filament\Concerns\HasStandardExports;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

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
            ->filters([
                Filter::make('created_at')
                    ->form([
                        DatePicker::make('created_from')->label('From date'),
                        DatePicker::make('created_until')->label('To date'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when($data['created_from'] ?? null, fn (Builder $q) => $q->whereDate('created_at', '>=', $data['created_from']))
                            ->when($data['created_until'] ?? null, fn (Builder $q) => $q->whereDate('created_at', '<=', $data['created_until']));
                    })
                    ->indicateUsing(function (array $data): array {
                        $indicators = [];
                        if ($data['created_from'] ?? null) {
                            $indicators[] = \Filament\Tables\Filters\Indicator::make('From '.$data['created_from'])->removeField('created_from');
                        }
                        if ($data['created_until'] ?? null) {
                            $indicators[] = \Filament\Tables\Filters\Indicator::make('Until '.$data['created_until'])->removeField('created_until');
                        }
                        return $indicators;
                    }),
                SelectFilter::make('client_contact_id')
                    ->label('Client')
                    ->relationship('clientContact', 'first_name', modifyQueryUsing: fn ($q) => $q->orderBy('first_name'))
                    ->getOptionLabelFromRecordUsing(fn ($record) => trim($record->first_name.' '.$record->last_name))
                    ->searchable()
                    ->preload(),
                SelectFilter::make('sales_agent_contact_id')
                    ->label('Sales agent')
                    ->relationship('salesAgentContact', 'first_name', modifyQueryUsing: fn ($q) => $q->orderBy('first_name'))
                    ->getOptionLabelFromRecordUsing(fn ($record) => trim($record->first_name.' '.$record->last_name))
                    ->searchable()
                    ->preload(),
            ])
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
