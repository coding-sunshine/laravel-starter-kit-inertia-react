<?php

declare(strict_types=1);

namespace App\Filament\Resources\Contacts;

use App\Filament\Concerns\ScopesToCurrentTenant;
use App\Filament\Resources\Contacts\Pages\ListContacts;
use App\Models\Contact;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

final class ContactResource extends Resource
{
    use ScopesToCurrentTenant;

    protected static ?string $model = Contact::class;

    protected static ?string $recordTitleAttribute = 'first_name';

    protected static string|UnitEnum|null $navigationGroup = 'CRM';

    protected static ?int $navigationSort = 1;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUsers;

    protected static ?string $navigationLabel = 'Contacts / Leads';

    public static function getModelLabel(): string
    {
        return 'Contact';
    }

    public static function getPluralModelLabel(): string
    {
        return 'Contacts / Leads';
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([]);
    }

    public static function table(Table $table): Table
    {
        return \App\Filament\Resources\Contacts\Tables\ContactsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListContacts::route('/'),
        ];
    }

    public static function canCreate(): bool
    {
        return false;
    }
}
