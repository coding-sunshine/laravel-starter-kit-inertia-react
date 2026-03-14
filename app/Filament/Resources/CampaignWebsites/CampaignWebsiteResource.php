<?php

declare(strict_types=1);

namespace App\Filament\Resources\CampaignWebsites;

use App\Filament\Resources\CampaignWebsites\Pages\CreateCampaignWebsite;
use App\Filament\Resources\CampaignWebsites\Pages\EditCampaignWebsite;
use App\Filament\Resources\CampaignWebsites\Pages\ListCampaignWebsites;
use App\Filament\Resources\CampaignWebsites\Schemas\CampaignWebsiteForm;
use App\Filament\Resources\CampaignWebsites\Tables\CampaignWebsitesTable;
use App\Models\CampaignWebsite;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

final class CampaignWebsiteResource extends Resource
{
    protected static ?string $model = CampaignWebsite::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    public static function form(Schema $schema): Schema
    {
        return CampaignWebsiteForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return CampaignWebsitesTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCampaignWebsites::route('/'),
            'create' => CreateCampaignWebsite::route('/create'),
            'edit' => EditCampaignWebsite::route('/{record}/edit'),
        ];
    }

    public static function getRecordRouteBindingEloquentQuery(): Builder
    {
        return parent::getRecordRouteBindingEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }
}
