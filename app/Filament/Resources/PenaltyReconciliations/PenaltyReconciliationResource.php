<?php

declare(strict_types=1);

namespace App\Filament\Resources\PenaltyReconciliations;

use App\Filament\Resources\PenaltyReconciliations\Pages\ListPenaltyReconciliations;
use App\Filament\Resources\PenaltyReconciliations\Pages\ViewPenaltyReconciliation;
use App\Models\PenaltyReconciliation;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use UnitEnum;

final class PenaltyReconciliationResource extends Resource
{
    protected static ?string $model = PenaltyReconciliation::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::Scale;

    protected static string|UnitEnum|null $navigationGroup = 'Penalties';

    protected static ?string $navigationLabel = 'Reconciliation';

    public static function form(Schema $schema): Schema
    {
        return $schema; // read-only resource
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('rake.rake_number')->label('Rake')->searchable()->sortable(),
                TextColumn::make('penalty_code')->label('Head')->badge()->sortable(),
                TextColumn::make('predicted_amount')->money('INR')->sortable(),
                TextColumn::make('billed_amount')->money('INR')->sortable(),
                TextColumn::make('variance')->money('INR')->sortable()
                    ->color(fn (PenaltyReconciliation $r): string => $r->variance > 0 ? 'danger' : 'success'),
                TextColumn::make('variance_pct')->label('Variance %')->numeric(2)->sortable(),
                IconColumn::make('dispute_candidate')->boolean()->label('Dispute?'),
                TextColumn::make('reconciled_at')->dateTime()->sortable(),
            ])
            ->defaultSort('reconciled_at', 'desc')
            ->filters([
                SelectFilter::make('penalty_code')->options([
                    'DEM' => 'DEM',
                    'PLO' => 'PLO',
                    'POL1' => 'POL1',
                    'POLA' => 'POLA',
                    'ENHC' => 'ENHC',
                ]),
                TernaryFilter::make('dispute_candidate'),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPenaltyReconciliations::route('/'),
            'view' => ViewPenaltyReconciliation::route('/{record}'),
        ];
    }
}
