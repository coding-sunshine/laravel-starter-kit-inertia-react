<?php

declare(strict_types=1);

namespace App\Filament\Resources\ReferralPartners;

use App\Filament\Concerns\ScopesToCurrentTenant;
use App\Filament\Resources\ReferralPartners\Pages\ListReferralPartners;
use App\Filament\Resources\ReferralPartners\Tables\ReferralPartnersTable;
use App\Models\Partner;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

final class ReferralPartnerResource extends Resource
{
    use ScopesToCurrentTenant;

    protected static ?string $model = Partner::class;

    protected static ?string $recordTitleAttribute = 'id';

    protected static string|UnitEnum|null $navigationGroup = 'Accounts';

    protected static ?int $navigationSort = 7;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedHandRaised;

    protected static ?string $navigationLabel = 'Referral Partners';

    public static function getModelLabel(): string
    {
        return 'Referral Partner';
    }

    public static function getPluralModelLabel(): string
    {
        return 'Referral Partners';
    }

    public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        return parent::getEloquentQuery()
            ->where(function ($query) {
                $query->where('role', 'Referral Partner')
                    ->orWhere('role', 'Referral')
                    ->orWhere('role', 'like', '%Referral%');
            });
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([]);
    }

    public static function table(Table $table): Table
    {
        return ReferralPartnersTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListReferralPartners::route('/'),
        ];
    }

    public static function canCreate(): bool
    {
        return false;
    }
}
