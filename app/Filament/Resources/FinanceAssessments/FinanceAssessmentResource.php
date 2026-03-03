<?php

declare(strict_types=1);

namespace App\Filament\Resources\FinanceAssessments;

use App\Filament\Concerns\ScopesToCurrentTenant;
use App\Filament\Resources\FinanceAssessments\Pages\ListFinanceAssessments;
use App\Models\FinanceAssessment;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

final class FinanceAssessmentResource extends Resource
{
    use ScopesToCurrentTenant;

    protected static ?string $model = FinanceAssessment::class;

    protected static string|UnitEnum|null $navigationGroup = 'CRM';

    protected static ?int $navigationSort = 72;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBanknotes;

    protected static ?string $navigationLabel = 'Finance Assessments';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([]);
    }

    public static function table(Table $table): Table
    {
        return Tables\FinanceAssessmentsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListFinanceAssessments::route('/'),
        ];
    }

    public static function canCreate(): bool
    {
        return false;
    }
}
