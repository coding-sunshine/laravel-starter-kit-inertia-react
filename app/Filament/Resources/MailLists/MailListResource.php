<?php

declare(strict_types=1);

namespace App\Filament\Resources\MailLists;

use App\Filament\Concerns\ScopesToCurrentTenant;
use App\Filament\Resources\MailLists\Pages\CreateMailList;
use App\Filament\Resources\MailLists\Pages\ListMailLists;
use App\Filament\Resources\MailLists\Pages\ViewMailList;
use App\Filament\Resources\MailLists\Schemas\MailListForm;
use App\Filament\Resources\MailLists\Schemas\MailListInfolist;
use App\Filament\Resources\MailLists\Tables\MailListsTable;
use App\Models\MailList;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

final class MailListResource extends Resource
{
    use ScopesToCurrentTenant;

    protected static ?string $model = MailList::class;

    protected static string|UnitEnum|null $navigationGroup = null;

    protected static ?int $navigationSort = 2;

    protected static ?string $recordTitleAttribute = 'name';

    protected static ?string $navigationLabel = 'Mail List';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedEnvelope;

    public static function form(Schema $schema): Schema
    {
        return MailListForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return MailListInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return MailListsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListMailLists::route('/'),
            'create' => CreateMailList::route('/create'),
            'view' => ViewMailList::route('/{record}'),
        ];
    }
}
