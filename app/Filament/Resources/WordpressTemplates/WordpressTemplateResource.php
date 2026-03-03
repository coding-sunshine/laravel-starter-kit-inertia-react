<?php

declare(strict_types=1);

namespace App\Filament\Resources\WordpressTemplates;

use App\Filament\Resources\WordpressTemplates\Pages\ListWordpressTemplates;
use App\Models\WordpressTemplate;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

final class WordpressTemplateResource extends Resource
{
    protected static ?string $model = WordpressTemplate::class;

    protected static ?string $recordTitleAttribute = 'name';

    protected static string|UnitEnum|null $navigationGroup = 'Websites';

    protected static ?int $navigationSort = 92;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentText;

    protected static ?string $navigationLabel = 'WordPress Templates';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([]);
    }

    public static function table(Table $table): Table
    {
        return Tables\WordpressTemplatesTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListWordpressTemplates::route('/'),
        ];
    }

    public static function canCreate(): bool
    {
        return false;
    }
}
