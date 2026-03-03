<?php

declare(strict_types=1);

namespace App\Filament\Resources\FlyerTemplates;

use App\Filament\Concerns\ScopesToCurrentTenant;
use App\Filament\Resources\FlyerTemplates\Pages\ListFlyerTemplates;
use App\Models\FlyerTemplate;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

final class FlyerTemplateResource extends Resource
{
    use ScopesToCurrentTenant;

    protected static ?string $model = FlyerTemplate::class;

    protected static ?string $recordTitleAttribute = 'name';

    protected static string|UnitEnum|null $navigationGroup = 'Properties';

    protected static ?int $navigationSort = 86;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentDuplicate;

    protected static ?string $navigationLabel = 'Flyer Templates';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([]);
    }

    public static function table(Table $table): Table
    {
        return Tables\FlyerTemplatesTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListFlyerTemplates::route('/'),
        ];
    }

    public static function canCreate(): bool
    {
        return false;
    }
}
