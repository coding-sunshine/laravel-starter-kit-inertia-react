<?php

declare(strict_types=1);

namespace App\Filament\Resources\AiBot;

use App\Filament\Concerns\ScopesToCurrentTenant;
use App\Filament\Resources\AiBot\Pages\CreateAiBotCategory;
use App\Filament\Resources\AiBot\Pages\EditAiBotCategory;
use App\Filament\Resources\AiBot\Pages\ListAiBotCategories;
use App\Filament\Resources\AiBot\Pages\ViewAiBotCategory;
use App\Models\AiBotCategory;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use UnitEnum;

final class AiBotCategoryResource extends Resource
{
    use ScopesToCurrentTenant;

    protected static ?string $model = AiBotCategory::class;

    protected static ?string $recordTitleAttribute = 'name';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedFolder;

    protected static string|UnitEnum|null $navigationGroup = 'Bot Management';

    protected static ?int $navigationSort = 1;

    protected static ?string $navigationLabel = 'Bot In A Box Category';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')->required()->maxLength(255),
                TextInput::make('slug')->maxLength(255),
                TextInput::make('order_column')->numeric()->default(0),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->searchable()->sortable(),
                TextColumn::make('slug')->searchable()->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('order_column')->sortable()->toggleable(),
                TextColumn::make('prompt_commands_count')->counts('promptCommands')->label('Commands'),
            ])
            ->defaultSort('order_column')
            ->recordActions([EditAction::make(), DeleteAction::make()])
            ->toolbarActions([BulkActionGroup::make([DeleteBulkAction::make()])]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListAiBotCategories::route('/'),
            'create' => CreateAiBotCategory::route('/create'),
            'view' => ViewAiBotCategory::route('/{record}'),
            'edit' => EditAiBotCategory::route('/{record}/edit'),
        ];
    }
}
