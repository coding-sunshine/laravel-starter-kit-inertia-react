<?php

declare(strict_types=1);

namespace App\Filament\Pages;

use App\Settings\PermissionSettings;
use BackedEnum;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Pages\SettingsPage;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

final class ManagePermissions extends SettingsPage
{
    protected static string|UnitEnum|null $navigationGroup = null;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedLockClosed;

    protected static ?string $navigationLabel = 'Permission Settings';

    protected static ?int $navigationSort = 30;

    protected static string $settings = PermissionSettings::class;

    public static function shouldRegisterNavigation(): bool
    {
        return false;
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Toggle::make('teams_enabled')
                    ->label('Teams enabled'),
                TextInput::make('team_foreign_key')
                    ->label('Team foreign key'),
            ]);
    }
}
