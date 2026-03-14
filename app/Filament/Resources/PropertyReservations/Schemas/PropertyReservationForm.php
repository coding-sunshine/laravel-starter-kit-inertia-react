<?php

declare(strict_types=1);

namespace App\Filament\Resources\PropertyReservations\Schemas;

use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

final class PropertyReservationForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Property')
                    ->columns(2)
                    ->schema([
                        Select::make('project_id')
                            ->relationship('project', 'name')
                            ->searchable()
                            ->preload()
                            ->label('Project'),
                        Select::make('lot_id')
                            ->relationship('lot', 'lot_number')
                            ->searchable()
                            ->preload()
                            ->label('Lot'),
                    ]),

                Section::make('Contacts')
                    ->columns(2)
                    ->schema([
                        Select::make('primary_contact_id')
                            ->relationship('primaryContact', 'first_name')
                            ->searchable()
                            ->preload()
                            ->label('Primary Contact'),
                        Select::make('secondary_contact_id')
                            ->relationship('secondaryContact', 'first_name')
                            ->searchable()
                            ->preload()
                            ->label('Secondary Contact'),
                        Select::make('agent_contact_id')
                            ->relationship('agentContact', 'first_name')
                            ->searchable()
                            ->preload()
                            ->label('Agent'),
                    ]),

                Section::make('Stage & Status')
                    ->columns(2)
                    ->schema([
                        Select::make('stage')
                            ->required()
                            ->default('enquiry')
                            ->options([
                                'enquiry' => 'Enquiry',
                                'qualified' => 'Qualified',
                                'reservation' => 'Reservation',
                                'contract' => 'Contract',
                                'unconditional' => 'Unconditional',
                                'settled' => 'Settled',
                            ]),
                        Select::make('deposit_status')
                            ->required()
                            ->default('pending')
                            ->options([
                                'pending' => 'Pending',
                                'paid' => 'Paid',
                                'waived' => 'Waived',
                                'refunded' => 'Refunded',
                            ]),
                        TextInput::make('purchase_price')
                            ->numeric()
                            ->prefix('$')
                            ->label('Purchase Price'),
                        Select::make('purchaser_type')
                            ->multiple()
                            ->options([
                                'individual' => 'Individual',
                                'company' => 'Company',
                                'smsf' => 'SMSF',
                                'trust' => 'Trust',
                            ])
                            ->label('Purchaser Type'),
                    ]),

                Section::make('Finance & Conveyancing')
                    ->columns(2)
                    ->collapsed()
                    ->schema([
                        Toggle::make('finance_condition')
                            ->label('Finance Condition'),
                        TextInput::make('finance_days')
                            ->numeric()
                            ->label('Finance Days'),
                        TextInput::make('broker')
                            ->maxLength(255)
                            ->label('Finance Broker'),
                        TextInput::make('firm')
                            ->maxLength(255)
                            ->label('Conveyancing Firm'),
                        TextInput::make('trustee_name')
                            ->maxLength(255)
                            ->label('Trustee Name'),
                        TextInput::make('abn_acn')
                            ->maxLength(50)
                            ->label('ABN / ACN'),
                    ]),

                Section::make('Deposit & Payments')
                    ->columns(2)
                    ->collapsed()
                    ->schema([
                        TextInput::make('deposit')
                            ->numeric()
                            ->prefix('$')
                            ->label('Deposit Amount'),
                        TextInput::make('deposit_bal')
                            ->numeric()
                            ->prefix('$')
                            ->label('Deposit Balance'),
                        TextInput::make('build_deposit')
                            ->numeric()
                            ->prefix('$')
                            ->label('Build Deposit'),
                        DatePicker::make('payment_duedate')
                            ->label('Payment Due Date'),
                    ]),

                Section::make('Agreement')
                    ->columns(2)
                    ->collapsed()
                    ->schema([
                        Checkbox::make('agree_lawlab')
                            ->label('Agree LawLab'),
                        Checkbox::make('agree')
                            ->label('Agreement Signed'),
                        DatePicker::make('agree_date')
                            ->label('Agreement Date'),
                        DatePicker::make('contract_send')
                            ->label('Contract Sent'),
                        Checkbox::make('smsf_trust_setup')
                            ->label('SMSF Trust Setup'),
                        Checkbox::make('bare_trust_setup')
                            ->label('Bare Trust Setup'),
                        Checkbox::make('funds_rollover')
                            ->label('Funds Rollover'),
                    ]),

                Section::make('Notes')
                    ->collapsed()
                    ->schema([
                        Textarea::make('notes')
                            ->rows(4)
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
