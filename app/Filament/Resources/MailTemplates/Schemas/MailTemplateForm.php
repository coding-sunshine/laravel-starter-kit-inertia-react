<?php

declare(strict_types=1);

namespace App\Filament\Resources\MailTemplates\Schemas;

use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

final class MailTemplateForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Template')
                    ->schema([
                        TextInput::make('name')
                            ->required()
                            ->maxLength(255)
                            ->readOnly()
                            ->helperText('Internal name (edit in seeder to change)'),
                        TextInput::make('event')
                            ->label('Event class')
                            ->required()
                            ->maxLength(255)
                            ->readOnly(),
                        TextInput::make('subject')
                            ->required()
                            ->maxLength(255)
                            ->columnSpanFull()
                            ->helperText('Blade: use event properties e.g. {{ $user->name }}'),
                        Textarea::make('body')
                            ->required()
                            ->rows(12)
                            ->columnSpanFull()
                            ->helperText('HTML + Blade. Event properties available as variables.'),
                        TextInput::make('delay')
                            ->maxLength(255)
                            ->placeholder('e.g. 1 hour, 1 day')
                            ->helperText('Optional delay before sending (Carbon interval string)'),
                        Toggle::make('is_active')
                            ->label('Active')
                            ->helperText('Only active templates are sent when the event is dispatched')
                            ->default(true),
                    ])->columns(2),
            ]);
    }
}
