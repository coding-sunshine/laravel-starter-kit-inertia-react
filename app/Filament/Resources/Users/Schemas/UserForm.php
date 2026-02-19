<?php

declare(strict_types=1);

namespace App\Filament\Resources\Users\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

final class UserForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->required(),
                TextInput::make('email')
                    ->label('Email address')
                    ->email()
                    ->required(),
                Select::make('roles')
                    ->relationship(titleAttribute: 'name')
                    ->multiple()
                    ->preload()
                    ->searchable(),
                Select::make('sidings')
                    ->relationship(
                        'sidings',
                        'name',
                        fn ($query) => tenant_id() ? $query->where('organization_id', tenant_id()) : $query
                    )
                    ->multiple()
                    ->preload()
                    ->searchable()
                    ->live()
                    ->helperText('RRMCS: Sidings this user can access.'),
                Select::make('primary_siding_id')
                    ->label('Primary siding')
                    ->options(function ($get): array {
                        $ids = $get('sidings') ?? [];
                        if ($ids === [] || ! is_array($ids)) {
                            return [];
                        }

                        return \App\Models\Siding::query()
                            ->whereIn('id', $ids)
                            ->when(tenant_id(), fn ($q) => $q->where('organization_id', tenant_id()))
                            ->pluck('name', 'id')
                            ->all();
                    })
                    ->searchable()
                    ->helperText('Optional: default siding for this user.'),
                TagsInput::make('tag_names')
                    ->label('Tags')
                    ->placeholder('Add a tag')
                    ->suggestions(
                        fn (): array => \Spatie\Tags\Tag::query()->pluck('name')->unique()->values()->all()
                    ),
                DateTimePicker::make('email_verified_at'),
                TextInput::make('password')
                    ->password()
                    ->required(),
                Textarea::make('two_factor_secret')
                    ->columnSpanFull(),
                Textarea::make('two_factor_recovery_codes')
                    ->columnSpanFull(),
                DateTimePicker::make('two_factor_confirmed_at'),
            ]);
    }
}
