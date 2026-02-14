<?php

declare(strict_types=1);

namespace App\Filament\Resources\Billing\Affiliates;

use App\Filament\Resources\Billing\Affiliates\Pages\ManageAffiliates;
use App\Models\Billing\Affiliate;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use UnitEnum;

final class AffiliateResource extends Resource
{
    protected static ?string $model = Affiliate::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static string|UnitEnum|null $navigationGroup = 'Billing';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make()
                    ->schema([
                        Select::make('user_id')
                            ->relationship('user', 'name')
                            ->required()
                            ->searchable(),
                        TextInput::make('affiliate_code')
                            ->disabled(),
                        Select::make('status')
                            ->options([
                                Affiliate::STATUS_PENDING => 'Pending',
                                Affiliate::STATUS_ACTIVE => 'Active',
                                Affiliate::STATUS_SUSPENDED => 'Suspended',
                                Affiliate::STATUS_REJECTED => 'Rejected',
                            ])
                            ->required(),
                        TextInput::make('commission_rate')
                            ->numeric()
                            ->suffix('%')
                            ->default(20),
                        TextInput::make('payment_email')
                            ->email(),
                        Select::make('payment_method')
                            ->options([
                                'paypal' => 'PayPal',
                                'bank_transfer' => 'Bank Transfer',
                            ])
                            ->default('paypal'),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('user.name')->searchable()->sortable(),
                TextColumn::make('affiliate_code')->searchable(),
                TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        Affiliate::STATUS_ACTIVE => 'success',
                        Affiliate::STATUS_PENDING => 'warning',
                        Affiliate::STATUS_SUSPENDED => 'gray',
                        Affiliate::STATUS_REJECTED => 'danger',
                        default => 'gray',
                    }),
                TextColumn::make('commission_rate')->suffix('%'),
                TextColumn::make('total_earnings')->money(config('billing.currency', 'usd')),
                TextColumn::make('pending_earnings')->money(config('billing.currency', 'usd')),
                TextColumn::make('total_referrals'),
                TextColumn::make('successful_conversions'),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options([
                        Affiliate::STATUS_PENDING => 'Pending',
                        Affiliate::STATUS_ACTIVE => 'Active',
                        Affiliate::STATUS_SUSPENDED => 'Suspended',
                        Affiliate::STATUS_REJECTED => 'Rejected',
                    ]),
            ])
            ->recordActions([
                Action::make('approve')
                    ->action(fn (Affiliate $record) => $record->approve())
                    ->requiresConfirmation()
                    ->visible(fn (Affiliate $record): bool => $record->isPending())
                    ->color('success'),
                Action::make('suspend')
                    ->action(fn (Affiliate $record) => $record->suspend())
                    ->requiresConfirmation()
                    ->visible(fn (Affiliate $record): bool => $record->isActive())
                    ->color('warning'),
                Action::make('reject')
                    ->action(fn (Affiliate $record) => $record->reject())
                    ->requiresConfirmation()
                    ->visible(fn (Affiliate $record): bool => $record->isPending())
                    ->color('danger'),
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageAffiliates::route('/'),
        ];
    }
}
