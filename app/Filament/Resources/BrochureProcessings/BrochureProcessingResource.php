<?php

declare(strict_types=1);

namespace App\Filament\Resources\BrochureProcessings;

use App\Filament\Resources\BrochureProcessings\Pages\CreateBrochureProcessing;
use App\Filament\Resources\BrochureProcessings\Pages\EditBrochureProcessing;
use App\Filament\Resources\BrochureProcessings\Pages\ListBrochureProcessings;
use App\Filament\Resources\BrochureProcessings\Schemas\BrochureProcessingForm;
use App\Filament\Resources\BrochureProcessings\Tables\BrochureProcessingsTable;
use App\Models\BrochureProcessing;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

final class BrochureProcessingResource extends Resource
{
    protected static ?string $model = BrochureProcessing::class;

    protected static string|UnitEnum|null $navigationGroup = 'AI Bot';

    protected static ?int $navigationSort = 15;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocument;

    protected static ?string $navigationLabel = 'Document Processing';

    protected static ?string $pluralLabel = 'Document Processing';

    protected static ?string $recordTitleAttribute = 'id';

    public static function form(Schema $schema): Schema
    {
        return BrochureProcessingForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return BrochureProcessingsTable::configure($table);
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
            'index' => ListBrochureProcessings::route('/'),
            'create' => CreateBrochureProcessing::route('/create'),
            'edit' => EditBrochureProcessing::route('/{record}/edit'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return (string) static::getModel()::query()
            ->where('status', 'pending_approval')
            ->count();
    }

    public static function getNavigationBadgeColor(): string|array|null
    {
        return static::getNavigationBadge() > 0 ? 'warning' : null;
    }
}
