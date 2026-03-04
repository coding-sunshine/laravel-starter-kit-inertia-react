<?php

declare(strict_types=1);

namespace App\Filament\Resources\BDMs;

use App\Filament\Concerns\ScopesToCurrentTenant;
use App\Filament\Resources\BDMs\Pages\ListBDMs;
use App\Filament\Resources\BDMs\Tables\BDMsTable;
use App\Models\Contact;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

final class BDMResource extends Resource
{
    use ScopesToCurrentTenant;

    protected static ?string $model = Contact::class;

    protected static ?string $recordTitleAttribute = 'first_name';

    protected static string|UnitEnum|null $navigationGroup = 'Accounts';

    protected static ?int $navigationSort = 5;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBriefcase;

    protected static ?string $navigationLabel = 'BDMs';

    public static function getModelLabel(): string
    {
        return 'BDM';
    }

    public static function getPluralModelLabel(): string
    {
        return 'Business Development Managers';
    }

    public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        return parent::getEloquentQuery()
            ->where(function ($query) {
                $query->where('type', 'BDM')
                    ->orWhere('type', 'Business Development Manager')
                    ->orWhere('job_title', 'like', '%BDM%')
                    ->orWhere('job_title', 'like', '%Business Development%');
            });
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([]);
    }

    public static function table(Table $table): Table
    {
        return BDMsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListBDMs::route('/'),
        ];
    }

    public static function canCreate(): bool
    {
        return false;
    }
}
