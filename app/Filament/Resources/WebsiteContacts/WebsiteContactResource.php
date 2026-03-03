<?php

declare(strict_types=1);

namespace App\Filament\Resources\WebsiteContacts;

use App\Filament\Concerns\ScopesToCurrentTenant;
use App\Filament\Resources\WebsiteContacts\Pages\ListWebsiteContacts;
use App\Models\WebsiteContact;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

final class WebsiteContactResource extends Resource
{
    use ScopesToCurrentTenant;

    protected static ?string $model = WebsiteContact::class;

    protected static string|UnitEnum|null $navigationGroup = 'CRM';

    protected static ?int $navigationSort = 70;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUserCircle;

    protected static ?string $navigationLabel = 'Website Contacts';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([]);
    }

    public static function table(Table $table): Table
    {
        return Tables\WebsiteContactsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListWebsiteContacts::route('/'),
        ];
    }

    public static function canCreate(): bool
    {
        return false;
    }
}
