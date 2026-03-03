<?php

declare(strict_types=1);

namespace App\Filament\Resources\WordpressWebsites;

use App\Filament\Concerns\ScopesToCurrentTenant;
use App\Filament\Resources\WordpressWebsites\Pages\ListWordpressWebsites;
use App\Models\WordpressWebsite;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

final class WordpressWebsiteResource extends Resource
{
    use ScopesToCurrentTenant;

    protected static ?string $model = WordpressWebsite::class;

    protected static ?string $recordTitleAttribute = 'name';

    protected static string|UnitEnum|null $navigationGroup = 'Websites';

    protected static ?int $navigationSort = 91;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCodeBracket;

    protected static ?string $navigationLabel = 'WordPress Websites';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([]);
    }

    public static function table(Table $table): Table
    {
        return Tables\WordpressWebsitesTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListWordpressWebsites::route('/'),
        ];
    }

    public static function canCreate(): bool
    {
        return false;
    }
}
