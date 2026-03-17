<?php

declare(strict_types=1);

namespace App\Filament\Resources\Users\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TagsInput;
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
                    ->label('Roles')
                    ->multiple()
                    ->preload()
                    ->searchable()
                    ->options(function (): array {
                        $teamKey = config('permission.column_names.team_foreign_key', 'organization_id');

                        return \Spatie\Permission\Models\Role::query()
                            ->whereIn($teamKey, [0, null])
                            ->pluck('name', 'id')
                            ->all();
                    }),
                Select::make('sidings')
                    ->relationship('sidings', 'name')
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
                Select::make('siding_shifts')
                    ->label('Shifts')
                    ->relationship(
                        'sidingShifts',
                        'shift_name',
                        fn ($query) => $query->where('siding_shifts.is_active', true)->orderBy('siding_shifts.sort_order')
                    )
                    ->multiple()
                    ->preload()
                    ->searchable()
                    ->helperText('Optional: active shifts this user is assigned to.'),
                TagsInput::make('tag_names')
                    ->label('Tags')
                    ->placeholder('Add a tag')
                    ->suggestions(
                        fn (): array => \Spatie\Tags\Tag::query()->pluck('name')->unique()->values()->all()
                    ),
                TextInput::make('password')
                    ->password()
                    ->same('password_confirmation')
                    ->visible(fn (string $context): bool => $context === 'create')
                    ->required(fn (string $context): bool => $context === 'create'),
                TextInput::make('password_confirmation')
                    ->password()
                    ->label('Confirm password')
                    ->visible(fn (string $context): bool => $context === 'create')
                    ->required(fn (string $context): bool => $context === 'create'),
            ]);
    }
}
