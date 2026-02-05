<?php

declare(strict_types=1);

namespace App\Filament\Resources\Roles\Tables;

use App\Filament\Resources\Roles\RoleResource;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Spatie\Permission\Models\Role;

final class RolesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('name')
            ->columns([
                TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('guard_name')
                    ->badge()
                    ->sortable(),
                TextColumn::make('permissions_count')
                    ->counts('permissions')
                    ->label('Permissions')
                    ->sortable(),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
                Action::make('duplicate')
                    ->label('Duplicate')
                    ->icon('heroicon-o-document-duplicate')
                    ->color('gray')
                    ->action(function (Role $record): \Illuminate\Http\RedirectResponse {
                        $baseName = 'Copy of '.$record->name;
                        $name = $baseName;
                        $counter = 1;
                        while (Role::query()->where('name', $name)->where('guard_name', $record->guard_name)->exists()) {
                            $name = $baseName.' ('.$counter.')';
                            $counter++;
                        }
                        $newRole = Role::query()->create([
                            'name' => $name,
                            'guard_name' => $record->guard_name,
                        ]);
                        $newRole->syncPermissions($record->permissions->pluck('name')->all());
                        Notification::make()
                            ->title('Role duplicated')
                            ->body("Created \"{$name}\" with same permissions.")
                            ->success()
                            ->send();

                        return redirect(RoleResource::getUrl('edit', ['record' => $newRole]));
                    }),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
