<?php

declare(strict_types=1);

namespace App\Filament\Resources\CampaignWebsites;

use App\Filament\Concerns\ScopesToCurrentTenant;
use App\Filament\Resources\CampaignWebsites\Pages\ListCampaignWebsites;
use App\Models\CampaignWebsite;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

final class CampaignWebsiteResource extends Resource
{
    use ScopesToCurrentTenant;

    protected static ?string $model = CampaignWebsite::class;

    protected static ?string $recordTitleAttribute = 'name';

    protected static string|UnitEnum|null $navigationGroup = 'Websites';

    protected static ?int $navigationSort = 93;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedMegaphone;

    protected static ?string $navigationLabel = 'Campaign Websites';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([]);
    }

    public static function table(Table $table): Table
    {
        return Tables\CampaignWebsitesTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCampaignWebsites::route('/'),
        ];
    }

    public static function canCreate(): bool
    {
        return false;
    }
}
