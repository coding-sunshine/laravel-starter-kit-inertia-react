<?php

declare(strict_types=1);

namespace App\Filament\Resources\Notes;

use App\Filament\Concerns\ScopesToCurrentTenant;
use App\Filament\Resources\Notes\Pages\CreateNote;
use App\Filament\Resources\Notes\Pages\ListNotes;
use App\Filament\Resources\Notes\Pages\ViewNote;
use App\Filament\Resources\Notes\Schemas\NoteForm;
use App\Filament\Resources\Notes\Schemas\NoteInfolist;
use App\Filament\Resources\Notes\Tables\NotesTable;
use App\Models\Note;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

final class NoteResource extends Resource
{
    use ScopesToCurrentTenant;

    protected static ?string $model = Note::class;

    protected static string|UnitEnum|null $navigationGroup = 'CRM';

    protected static ?int $navigationSort = 60;

    protected static ?string $recordTitleAttribute = 'id';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentText;

    public static function form(Schema $schema): Schema
    {
        return NoteForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return NoteInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return NotesTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListNotes::route('/'),
            'create' => CreateNote::route('/create'),
            'view' => ViewNote::route('/{record}'),
        ];
    }
}
