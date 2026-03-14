<?php

declare(strict_types=1);

namespace App\Filament\Resources\Contacts\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;

final class ContactsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('first_name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('last_name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('type')
                    ->badge()
                    ->sortable(),
                TextColumn::make('stage')
                    ->badge()
                    ->sortable(),
                TextColumn::make('contact_origin')
                    ->badge()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('company_name')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('lead_score')
                    ->numeric()
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('last_contacted_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('next_followup_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('organization.name')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('type')
                    ->options([
                        'lead' => 'Lead',
                        'client' => 'Client',
                        'partner' => 'Partner',
                        'affiliate' => 'Affiliate',
                        'finance_broker' => 'Finance Broker',
                        'conveyancer' => 'Conveyancer',
                        'accountant' => 'Accountant',
                        'developer' => 'Developer',
                        'saas_lead' => 'SaaS Lead',
                        'other' => 'Other',
                    ]),
                SelectFilter::make('contact_origin')
                    ->options([
                        'property' => 'Property',
                        'saas_product' => 'SaaS Product',
                    ]),
                SelectFilter::make('stage')
                    ->options([
                        'new' => 'New',
                        'nurture' => 'Nurture',
                        'not_interested' => 'Not Interested',
                        'hot' => 'Hot',
                        'property_reserved' => 'Property Reserved',
                        'signed_contract' => 'Signed Contract',
                    ]),
                TrashedFilter::make(),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    ForceDeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }
}
