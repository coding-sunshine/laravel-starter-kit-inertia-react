<?php

declare(strict_types=1);

namespace App\Filament\Resources\PropertyEnquiries;

use App\Filament\Concerns\ScopesToCurrentTenant;
use App\Filament\Resources\PropertyEnquiries\Pages\CreatePropertyEnquiry;
use App\Filament\Resources\PropertyEnquiries\Pages\EditPropertyEnquiry;
use App\Filament\Resources\PropertyEnquiries\Pages\ListPropertyEnquiries;
use App\Filament\Resources\PropertyEnquiries\Pages\ViewPropertyEnquiry;
use App\Filament\Resources\PropertyEnquiries\Schemas\PropertyEnquiryForm;
use App\Filament\Resources\PropertyEnquiries\Schemas\PropertyEnquiryInfolist;
use App\Filament\Resources\PropertyEnquiries\Tables\PropertyEnquiriesTable;
use App\Models\PropertyEnquiry;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

final class PropertyEnquiryResource extends Resource
{
    use ScopesToCurrentTenant;

    protected static ?string $model = PropertyEnquiry::class;

    protected static string|UnitEnum|null $navigationGroup = 'Online Forms';

    protected static ?int $navigationSort = 1;

    protected static ?string $recordTitleAttribute = 'id';

    protected static ?string $navigationLabel = 'Property Enquiry';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedEnvelope;

    public static function form(Schema $schema): Schema
    {
        return PropertyEnquiryForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return PropertyEnquiryInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return PropertyEnquiriesTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPropertyEnquiries::route('/'),
            'create' => CreatePropertyEnquiry::route('/create'),
            'view' => ViewPropertyEnquiry::route('/{record}'),
            'edit' => EditPropertyEnquiry::route('/{record}/edit'),
        ];
    }
}
