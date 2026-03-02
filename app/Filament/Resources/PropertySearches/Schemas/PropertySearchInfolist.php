<?php

declare(strict_types=1);

namespace App\Filament\Resources\PropertySearches\Schemas;

use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

final class PropertySearchInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('clientContact.full_name')->label('Client'),
                TextEntry::make('agentContact.full_name')->label('Agent'),
                TextEntry::make('preferred_location')->placeholder('-'),
                TextEntry::make('max_capacity')->placeholder('-'),
                TextEntry::make('no_of_bedrooms')->placeholder('-'),
                TextEntry::make('no_of_bathrooms')->placeholder('-'),
                TextEntry::make('no_of_carspaces')->placeholder('-'),
                TextEntry::make('property_type')->badge()->placeholder('-'),
                TextEntry::make('build_status')->badge()->placeholder('-'),
                IconEntry::make('preapproval')->boolean(),
                TextEntry::make('created_at')->dateTime(),
                TextEntry::make('updated_at')->dateTime(),
            ]);
    }
}
