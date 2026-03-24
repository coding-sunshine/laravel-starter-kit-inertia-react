<?php

declare(strict_types=1);

namespace App\Filament\Resources\Users\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Utilities\Get;
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
                    ->label('Role')
                    ->preload()
                    ->searchable()
                    ->live()
                    ->options(function (): array {
                        $teamKey = config('permission.column_names.team_foreign_key', 'organization_id');

                        return \Spatie\Permission\Models\Role::query()
                            ->whereIn($teamKey, [0, null])
                            ->pluck('name', 'id')
                            ->all();
                    }),
                Select::make('siding_id')
                    ->label('Siding')
                    ->preload()
                    ->searchable()
                    ->options(function (): array {
                        return \App\Models\Siding::query()
                            ->where('is_active', true)
                            ->orderBy('name')
                            ->pluck('name', 'id')
                            ->all();
                    })
                    ->helperText('Select one siding.'),
                Select::make('siding_shifts')
                    ->label('Shift')
                    ->relationship(
                        'sidingShifts',
                        'shift_name',
                        fn ($query) => $query->where('siding_shifts.is_active', true)->orderBy('siding_shifts.sort_order')
                    )
                    ->preload()
                    ->searchable()
                    ->visible(function (Get $get): bool {
                        $roleId = $get('roles');
                        if ($roleId === null || $roleId === '') {
                            return false;
                        }

                        $role = \Spatie\Permission\Models\Role::query()->find((int) $roleId);

                        return in_array($role?->name, ['user', 'empty-weighment-shift'], true);
                    })
                    ->helperText('Optional: active shift this user is assigned to.'),
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
