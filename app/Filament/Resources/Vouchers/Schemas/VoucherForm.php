<?php

declare(strict_types=1);

namespace App\Filament\Resources\Vouchers\Schemas;

use BeyondCode\Vouchers\Facades\Vouchers;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Get;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

final class VoucherForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Voucher Code')
                    ->schema([
                        TextInput::make('code')
                            ->label('Code')
                            ->required()
                            ->maxLength(32)
                            ->unique(ignoreRecord: true)
                            ->placeholder('e.g., SAVE20')
                            ->default(function (): string {
                                $codes = Vouchers::generate(1);

                                return mb_strtoupper((string) ($codes[0] ?? 'COUPON'));
                            }),

                        DatePicker::make('expires_at')
                            ->label('Expires at')
                            ->nullable()
                            ->minDate(now()),
                    ])
                    ->columns(2),

                Section::make('Discount')
                    ->schema([
                        Select::make('data.discount_type')
                            ->label('Type')
                            ->options([
                                'percentage' => 'Percentage',
                                'fixed' => 'Fixed amount',
                            ])
                            ->default('percentage')
                            ->live()
                            ->required(),

                        TextInput::make('data.discount_amount')
                            ->label('Amount')
                            ->numeric()
                            ->required()
                            ->minValue(0)
                            ->maxValue(fn (Get $get): int => $get('data.discount_type') === 'percentage' ? 100 : 999999)
                            ->suffix(fn (Get $get): string => $get('data.discount_type') === 'percentage' ? '%' : '')
                            ->default(10),
                    ])
                    ->columns(2),
            ]);
    }
}
