<?php

declare(strict_types=1);

namespace App\Filament\Resources\PropertyEnquiries\Schemas;

use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

final class PropertyEnquiryInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('clientContact.full_name')->label('Client'),
                TextEntry::make('agentContact.full_name')->label('Agent'),
                TextEntry::make('preferred_location')->placeholder('-'),
                TextEntry::make('max_capacity')->placeholder('-'),
                IconEntry::make('preapproval')->boolean(),
                IconEntry::make('cash_purchase')->boolean()->placeholder('-'),
                TextEntry::make('inspection_date')->date()->placeholder('-'),
                TextEntry::make('inspection_time')->placeholder('-'),
                TextEntry::make('created_at')->dateTime(),
                TextEntry::make('updated_at')->dateTime(),
            ]);
    }
}
