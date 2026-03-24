<?php

declare(strict_types=1);

namespace App\Filament\Resources\Users\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Support\HtmlString;

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
                    ->afterStateUpdated(function (Set $set, Get $get, $livewire): void {
                        $isSectionMode = self::roleUsesSectionAssignments($get);
                        $record = method_exists($livewire, 'getRecord') ? $livewire->getRecord() : null;

                        if ($isSectionMode) {
                            $set('siding_id_single', null);

                            if ($record !== null) {
                                $sidingIds = \Illuminate\Support\Facades\DB::table('user_siding')
                                    ->where('user_id', $record->getKey())
                                    ->orderByDesc('is_primary')
                                    ->orderBy('siding_id')
                                    ->pluck('siding_id')
                                    ->all();
                                $shiftIds = $record->sidingShifts()->pluck('siding_shifts.id')->all();

                                $set('siding_ids_multi', $sidingIds);
                                $set('siding_shifts_multi', $shiftIds);
                            }

                            return;
                        }

                        $set('siding_ids_multi', []);
                        $set('siding_shifts_multi', []);

                        if ($record !== null) {
                            $set('siding_id_single', $record->siding_id);
                        }
                    })
                    ->options(function (): array {
                        $teamKey = config('permission.column_names.team_foreign_key', 'organization_id');

                        return \Spatie\Permission\Models\Role::query()
                            ->whereIn($teamKey, [0, null])
                            ->pluck('name', 'id')
                            ->all();
                    }),
                Select::make('siding_ids_multi')
                    ->label('Sidings')
                    ->multiple()
                    ->live()
                    ->preload()
                    ->searchable()
                    ->visible(fn (Get $get): bool => self::roleUsesSectionAssignments($get))
                    ->hint(new HtmlString('<span wire:loading wire:target="data.roles">Loading sidings...</span>'))
                    ->options(function (): array {
                        return \App\Models\Siding::query()
                            ->where('is_active', true)
                            ->orderBy('name')
                            ->pluck('name', 'id')
                            ->all();
                    })
                    ->helperText('Select one or more sidings for section-based roles.'),
                Select::make('siding_id_single')
                    ->label('Siding')
                    ->live()
                    ->preload()
                    ->searchable()
                    ->visible(fn (Get $get): bool => ! self::roleUsesSectionAssignments($get))
                    ->hint(new HtmlString('<span wire:loading wire:target="data.roles">Loading siding...</span>'))
                    ->options(function (): array {
                        return \App\Models\Siding::query()
                            ->where('is_active', true)
                            ->orderBy('name')
                            ->pluck('name', 'id')
                            ->all();
                    })
                    ->helperText('Select one siding.'),
                Select::make('siding_shifts_multi')
                    ->label('Shifts')
                    ->multiple()
                    ->preload()
                    ->searchable()
                    ->visible(fn (Get $get): bool => self::roleUsesSectionAssignments($get))
                    ->hint(new HtmlString('<span wire:loading wire:target="data.roles">Loading shifts...</span>'))
                    ->options(function (Get $get): array {
                        $selectedSidingIds = $get('siding_ids_multi');
                        $sidingIds = array_values(array_filter(array_map(
                            intval(...),
                            is_array($selectedSidingIds) ? $selectedSidingIds : [$selectedSidingIds]
                        )));

                        if ($sidingIds === []) {
                            return [];
                        }

                        return \App\Models\SidingShift::query()
                            ->where('is_active', true)
                            ->whereIn('siding_id', $sidingIds)
                            ->orderBy('siding_id')
                            ->orderBy('sort_order')
                            ->pluck('shift_name', 'id')
                            ->all();
                    })
                    ->helperText('Optional: active shifts this user is assigned to.'),
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

    private static function roleUsesSectionAssignments(Get $get): bool
    {
        $roleId = $get('roles');
        if ($roleId === null || $roleId === '') {
            return false;
        }

        return \Spatie\Permission\Models\Role::query()
            ->whereKey((int) $roleId)
            ->where(function ($query): void {
                $query
                    ->whereHas('permissions', fn ($q) => $q->where('name', 'like', 'sections.railway_siding_record_data.%'))
                    ->orWhereHas('permissions', fn ($q) => $q->where('name', 'like', 'sections.railway_siding_empty_weighment.%'));
            })
            ->exists();
    }
}
