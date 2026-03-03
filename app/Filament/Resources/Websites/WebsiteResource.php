<?php

declare(strict_types=1);

namespace App\Filament\Resources\Websites;

use App\Filament\Concerns\ScopesToCurrentTenant;
use App\Filament\Resources\Websites\Pages\ListWebsites;
use App\Models\Website;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

final class WebsiteResource extends Resource
{
    use ScopesToCurrentTenant;

    protected static ?string $model = Website::class;

    protected static ?string $recordTitleAttribute = 'name';

    protected static string|UnitEnum|null $navigationGroup = 'Websites';

    protected static ?int $navigationSort = 90;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedGlobeAlt;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([]);
    }

    public static function table(Table $table): Table
    {
        return Tables\WebsitesTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListWebsites::route('/'),
        ];
    }

    public static function canCreate(): bool
    {
        return false;
    }
}
