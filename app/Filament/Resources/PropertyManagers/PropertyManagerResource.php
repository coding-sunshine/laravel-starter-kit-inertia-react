<?php

declare(strict_types=1);

namespace App\Filament\Resources\PropertyManagers;

use App\Filament\Concerns\ScopesToCurrentTenant;
use App\Filament\Resources\PropertyManagers\Pages\ListPropertyManagers;
use App\Filament\Resources\PropertyManagers\Tables\PropertyManagersTable;
use App\Models\Contact;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

final class PropertyManagerResource extends Resource
{
    use ScopesToCurrentTenant;

    protected static ?string $model = Contact::class;

    protected static ?string $recordTitleAttribute = 'first_name';

    protected static string|UnitEnum|null $navigationGroup = 'Accounts';

    protected static ?int $navigationSort = 9;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBuildingOffice2;

    protected static ?string $navigationLabel = 'Property Managers';

    public static function getModelLabel(): string
    {
        return 'Property Manager';
    }

    public static function getPluralModelLabel(): string
    {
        return 'Property Managers';
    }

    public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        return parent::getEloquentQuery()
            ->where(function ($query) {
                $query->where('type', 'Property Manager')
                    ->orWhere('job_title', 'like', '%Property Manager%')
                    ->orWhere('job_title', 'like', '%Property Management%');
            });
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([]);
    }

    public static function table(Table $table): Table
    {
        return PropertyManagersTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPropertyManagers::route('/'),
        ];
    }

    public static function canCreate(): bool
    {
        return false;
    }
}
