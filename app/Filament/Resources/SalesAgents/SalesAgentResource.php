<?php

declare(strict_types=1);

namespace App\Filament\Resources\SalesAgents;

use App\Filament\Concerns\ScopesToCurrentTenant;
use App\Filament\Resources\SalesAgents\Pages\ListSalesAgents;
use App\Filament\Resources\SalesAgents\Tables\SalesAgentsTable;
use App\Models\Contact;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

final class SalesAgentResource extends Resource
{
    use ScopesToCurrentTenant;

    protected static ?string $model = Contact::class;

    protected static ?string $recordTitleAttribute = 'first_name';

    protected static string|UnitEnum|null $navigationGroup = 'Accounts';

    protected static ?int $navigationSort = 6;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUserPlus;

    protected static ?string $navigationLabel = 'Sales Agents';

    public static function getModelLabel(): string
    {
        return 'Sales Agent';
    }

    public static function getPluralModelLabel(): string
    {
        return 'Sales Agents';
    }

    public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        return parent::getEloquentQuery()
            ->where(function ($query) {
                $query->where('type', 'Sales Agent')
                    ->orWhere('type', 'Sales')
                    ->orWhere('job_title', 'like', '%Sales Agent%')
                    ->orWhere('job_title', 'like', '%Sales%');
            });
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([]);
    }

    public static function table(Table $table): Table
    {
        return SalesAgentsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListSalesAgents::route('/'),
        ];
    }

    public static function canCreate(): bool
    {
        return false;
    }
}
