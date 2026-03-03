<?php

declare(strict_types=1);

namespace App\Filament\Resources\Questionnaires;

use App\Filament\Concerns\ScopesToCurrentTenant;
use App\Filament\Resources\Questionnaires\Pages\ListQuestionnaires;
use App\Models\Questionnaire;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

final class QuestionnaireResource extends Resource
{
    use ScopesToCurrentTenant;

    protected static ?string $model = Questionnaire::class;

    protected static string|UnitEnum|null $navigationGroup = 'CRM';

    protected static ?int $navigationSort = 71;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentCheck;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([]);
    }

    public static function table(Table $table): Table
    {
        return Tables\QuestionnairesTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListQuestionnaires::route('/'),
        ];
    }

    public static function canCreate(): bool
    {
        return false;
    }
}
