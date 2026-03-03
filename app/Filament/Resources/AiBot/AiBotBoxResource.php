<?php

declare(strict_types=1);

namespace App\Filament\Resources\AiBot;

use App\Filament\Concerns\ScopesToCurrentTenant;
use App\Filament\Resources\AiBot\Pages\CreateAiBotBox;
use App\Filament\Resources\AiBot\Pages\EditAiBotBox;
use App\Filament\Resources\AiBot\Pages\ListAiBotBoxes;
use App\Filament\Resources\AiBot\Pages\ViewAiBotBox;
use App\Models\AiBotBox;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use UnitEnum;

final class AiBotBoxResource extends Resource
{
    use ScopesToCurrentTenant;

    protected static ?string $model = AiBotBox::class;

    protected static ?string $recordTitleAttribute = 'title';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedSquare3Stack3d;

    protected static string|UnitEnum|null $navigationGroup = 'AI Bot';

    protected static ?int $navigationSort = 3;

    protected static ?string $navigationLabel = 'Bot boxes';

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
                TextInput::make('title')->required()->maxLength(255),
                Textarea::make('description')->rows(3),
                Textarea::make('page_overview')->rows(3),
                TextInput::make('type')->maxLength(255),
                TextInput::make('visibility')->maxLength(255),
                TextInput::make('status')->maxLength(255),
                TextInput::make('order_column')->numeric()->default(0),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('title')->searchable()->sortable(),
                TextColumn::make('category.name')->label('Category')->sortable(),
                TextColumn::make('type')->badge()->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('visibility')->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('status')->badge()->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('order_column')->sortable()->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('order_column')
            ->recordActions([EditAction::make(), DeleteAction::make()])
            ->toolbarActions([BulkActionGroup::make([DeleteBulkAction::make()])]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListAiBotBoxes::route('/'),
            'create' => CreateAiBotBox::route('/create'),
            'view' => ViewAiBotBox::route('/{record}'),
            'edit' => EditAiBotBox::route('/{record}/edit'),
        ];
    }
}
