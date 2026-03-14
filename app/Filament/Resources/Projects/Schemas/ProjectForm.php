<?php

declare(strict_types=1);

namespace App\Filament\Resources\Projects\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

final class ProjectForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Basic Info')
                    ->columns(2)
                    ->schema([
                        TextInput::make('title')
                            ->required()
                            ->columnSpanFull(),
                        Select::make('stage')
                            ->required()
                            ->options([
                                'pre_launch' => 'Pre-Launch',
                                'selling' => 'Selling',
                                'completed' => 'Completed',
                                'archived' => 'Archived',
                            ])
                            ->default('selling'),
                        Select::make('developer_id')
                            ->relationship('developer', 'name')
                            ->searchable()
                            ->preload(),
                        Select::make('projecttype_id')
                            ->relationship('projecttype', 'name')
                            ->searchable()
                            ->preload(),
                        TextInput::make('suburb'),
                        TextInput::make('state'),
                        TextInput::make('postcode'),
                        TextInput::make('estate'),
                        DatePicker::make('start_at'),
                        DatePicker::make('end_at'),
                        Textarea::make('description')
                            ->columnSpanFull(),
                        Textarea::make('description_summary')
                            ->columnSpanFull(),
                    ]),
                Section::make('Pricing & Lots')
                    ->columns(3)
                    ->schema([
                        TextInput::make('total_lots')->numeric(),
                        TextInput::make('min_price')->numeric()->prefix('$'),
                        TextInput::make('max_price')->numeric()->prefix('$'),
                        TextInput::make('avg_price')->numeric()->prefix('$'),
                        TextInput::make('min_rent')->numeric()->prefix('$'),
                        TextInput::make('max_rent')->numeric()->prefix('$'),
                        TextInput::make('avg_rent')->numeric()->prefix('$'),
                        TextInput::make('rent_yield')->numeric()->suffix('%'),
                    ]),
                Section::make('Flags')
                    ->columns(3)
                    ->schema([
                        Toggle::make('is_hot_property'),
                        Toggle::make('is_featured'),
                        Toggle::make('is_archived'),
                        Toggle::make('is_hidden'),
                        Toggle::make('is_smsf'),
                        Toggle::make('is_firb'),
                        Toggle::make('is_ndis'),
                        Toggle::make('is_cashflow_positive'),
                        Toggle::make('is_co_living'),
                        Toggle::make('is_high_cap_growth'),
                        Toggle::make('is_rooming'),
                        Toggle::make('is_rent_to_sell'),
                        Toggle::make('is_exclusive'),
                    ]),
                Grid::make(2)
                    ->schema([
                        TextInput::make('lat')->numeric(),
                        TextInput::make('lng')->numeric(),
                    ]),
            ]);
    }
}
