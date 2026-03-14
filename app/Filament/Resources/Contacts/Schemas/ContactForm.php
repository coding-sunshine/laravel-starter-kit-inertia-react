<?php

declare(strict_types=1);

namespace App\Filament\Resources\Contacts\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

final class ContactForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Identity')
                    ->columns(2)
                    ->schema([
                        TextInput::make('first_name')
                            ->required()
                            ->maxLength(255),
                        TextInput::make('last_name')
                            ->maxLength(255),
                        TextInput::make('job_title')
                            ->maxLength(255),
                        Select::make('type')
                            ->required()
                            ->default('lead')
                            ->options([
                                'lead' => 'Lead',
                                'client' => 'Client',
                                'partner' => 'Partner',
                                'affiliate' => 'Affiliate',
                                'finance_broker' => 'Finance Broker',
                                'conveyancer' => 'Conveyancer',
                                'accountant' => 'Accountant',
                                'developer' => 'Developer',
                                'insurance_agency' => 'Insurance Agency',
                                'event_coordinator' => 'Event Coordinator',
                                'saas_lead' => 'SaaS Lead',
                                'other' => 'Other',
                            ]),
                    ]),

                Section::make('CRM Classification')
                    ->columns(2)
                    ->schema([
                        Select::make('contact_origin')
                            ->required()
                            ->default('property')
                            ->options([
                                'property' => 'Property',
                                'saas_product' => 'SaaS Product',
                            ]),
                        Select::make('stage')
                            ->options([
                                'new' => 'New',
                                'nurture' => 'Nurture',
                                'not_interested' => 'Not Interested',
                                'hot' => 'Hot',
                                'settlement_handover' => 'Settlement Handover',
                                'call_back' => 'Call Back',
                                'unconditional' => 'Unconditional',
                                'property_reserved' => 'Property Reserved',
                                'signed_contract' => 'Signed Contract',
                                'crashed' => 'Crashed',
                                'bc_required' => 'BC Required',
                                'land_settled' => 'Land Settled',
                                'construction' => 'Construction',
                                'property_enquiry' => 'Property Enquiry',
                            ]),
                        Select::make('organization_id')
                            ->relationship('organization', 'name')
                            ->searchable()
                            ->preload(),
                        TextInput::make('lead_score')
                            ->numeric()
                            ->minValue(0)
                            ->maxValue(100),
                    ]),

                Section::make('Company')
                    ->columns(2)
                    ->schema([
                        Select::make('company_id')
                            ->relationship('company', 'name')
                            ->searchable()
                            ->preload(),
                        TextInput::make('company_name')
                            ->maxLength(255),
                        Select::make('source_id')
                            ->relationship('source', 'name')
                            ->searchable()
                            ->preload(),
                    ]),

                Section::make('Follow-up')
                    ->columns(3)
                    ->schema([
                        DateTimePicker::make('last_followup_at'),
                        DateTimePicker::make('next_followup_at'),
                        DateTimePicker::make('last_contacted_at'),
                    ]),
            ]);
    }
}
