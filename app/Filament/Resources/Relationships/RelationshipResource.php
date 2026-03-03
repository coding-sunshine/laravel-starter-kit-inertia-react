<?php

declare(strict_types=1);

namespace App\Filament\Resources\Relationships;

use App\Filament\Concerns\ScopesToCurrentTenant;
use App\Filament\Resources\Relationships\Pages\CreateRelationship;
use App\Filament\Resources\Relationships\Pages\ListRelationships;
use App\Filament\Resources\Relationships\Pages\ViewRelationship;
use App\Filament\Resources\Relationships\Schemas\RelationshipForm;
use App\Filament\Resources\Relationships\Schemas\RelationshipInfolist;
use App\Filament\Resources\Relationships\Tables\RelationshipsTable;
use App\Models\Relationship;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

final class RelationshipResource extends Resource
{
    use ScopesToCurrentTenant;

    protected static ?string $model = Relationship::class;

    protected static string|UnitEnum|null $navigationGroup = 'CRM';

    protected static ?int $navigationSort = 70;

    protected static ?string $recordTitleAttribute = 'id';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedArrowsRightLeft;

    public static function form(Schema $schema): Schema
    {
        return RelationshipForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return RelationshipInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return RelationshipsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListRelationships::route('/'),
            'create' => CreateRelationship::route('/create'),
            'view' => ViewRelationship::route('/{record}'),
        ];
    }
}
