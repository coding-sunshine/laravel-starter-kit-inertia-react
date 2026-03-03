<?php

declare(strict_types=1);

namespace App\Filament\Resources\Notes\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

final class NoteForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('noteable_type')
                    ->label('Related type')
                    ->options([
                        'App\\Models\\Contact' => 'Contact',
                        'App\\Models\\Sale' => 'Sale',
                        'App\\Models\\Project' => 'Project',
                    ])
                    ->required()
                    ->searchable(),
                TextInput::make('noteable_id')
                    ->label('Related ID')
                    ->numeric()
                    ->required(),
                Textarea::make('content')
                    ->required()
                    ->rows(4)
                    ->columnSpanFull(),
                TextInput::make('type')
                    ->maxLength(255),
            ]);
    }
}
