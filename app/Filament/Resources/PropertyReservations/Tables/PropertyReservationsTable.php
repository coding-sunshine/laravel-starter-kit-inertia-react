<?php

declare(strict_types=1);

namespace App\Filament\Resources\PropertyReservations\Tables;

use App\Filament\Concerns\HasStandardExports;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

final class PropertyReservationsTable
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
                SelectFilter::make('agent_contact_id')
                    ->label('Agent')
                    ->relationship(
                        'agentContact',
                        'first_name',
                        modifyQueryUsing: fn (Builder $query): Builder => $query->orderBy('first_name'),
                    )
                    ->getOptionLabelFromRecordUsing(fn ($record) => trim($record->first_name.' '.$record->last_name))
                    ->searchable()
                    ->preload(),
                SelectFilter::make('project_id')
                    ->label('Project')
                    ->relationship('project', 'title')
                    ->searchable()
                    ->preload(),
            ])
            ->columns([
                TextColumn::make('id')->numeric()->sortable(),
                TextColumn::make('primaryContact.full_name')->label('Primary contact')->searchable(['first_name', 'last_name']),
                TextColumn::make('agentContact.full_name')->label('Agent')->searchable(['first_name', 'last_name']),
                TextColumn::make('project.title')->label('Project')->searchable()->limit(30),
                TextColumn::make('lot.id')->label('Lot')->numeric()->sortable(),
                TextColumn::make('purchase_price')->money()->sortable(),
                TextColumn::make('agree_date')->date()->sortable(),
                TextColumn::make('created_at')->dateTime()->sortable()->toggleable(isToggledHiddenByDefault: true),
            ])
            ->headerActions([
                self::makeExportHeaderAction('property-reservations'),
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
