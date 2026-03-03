<?php

declare(strict_types=1);

namespace App\Filament\Resources\AiBot;

use App\Filament\Concerns\ScopesToCurrentTenant;
use App\Filament\Resources\AiBot\Pages\CreateAiBotPromptCommand;
use App\Filament\Resources\AiBot\Pages\EditAiBotPromptCommand;
use App\Filament\Resources\AiBot\Pages\ListAiBotPromptCommands;
use App\Filament\Resources\AiBot\Pages\ViewAiBotPromptCommand;
use App\Models\AiBotPromptCommand;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use UnitEnum;

final class AiBotPromptCommandResource extends Resource
{
    use ScopesToCurrentTenant;

    protected static ?string $model = AiBotPromptCommand::class;

    protected static ?string $recordTitleAttribute = 'name';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCommandLine;

    protected static string|UnitEnum|null $navigationGroup = 'AI Bot';

    protected static ?int $navigationSort = 2;

    protected static ?string $navigationLabel = 'Prompt commands';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('ai_bot_category_id')
                    ->label('Category')
                    ->relationship('category', 'name')
                    ->searchable()
                    ->preload()
                    ->nullable(),
                TextInput::make('name')->required()->maxLength(255),
                TextInput::make('slug')->maxLength(255),
                Textarea::make('prompt')->rows(5),
                Textarea::make('description')->rows(2),
                TextInput::make('type')->maxLength(255),
                Toggle::make('is_active')->default(true),
                TextInput::make('order_column')->numeric()->default(0),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->searchable()->sortable(),
                TextColumn::make('category.name')->label('Category')->sortable(),
                TextColumn::make('type')->badge()->toggleable(isToggledHiddenByDefault: true),
                IconColumn::make('is_active')->boolean()->sortable(),
                TextColumn::make('order_column')->sortable()->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('order_column')
            ->recordActions([EditAction::make(), DeleteAction::make()])
            ->toolbarActions([BulkActionGroup::make([DeleteBulkAction::make()])]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListAiBotPromptCommands::route('/'),
            'create' => CreateAiBotPromptCommand::route('/create'),
            'view' => ViewAiBotPromptCommand::route('/{record}'),
            'edit' => EditAiBotPromptCommand::route('/{record}/edit'),
        ];
    }
}
