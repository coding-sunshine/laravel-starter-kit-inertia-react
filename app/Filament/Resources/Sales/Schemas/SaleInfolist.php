<?php

declare(strict_types=1);

namespace App\Filament\Resources\Sales\Schemas;

use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

final class SaleInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('clientContact.full_name')->label('Client'),
                TextEntry::make('project.title')->label('Project'),
                TextEntry::make('lot.id')->label('Lot'),
                TextEntry::make('developer.legacy_developer_id')->label('Developer')->placeholder('-'),
                TextEntry::make('comms_in_total')->money()->placeholder('-'),
                TextEntry::make('comms_out_total')->money()->placeholder('-'),
                TextEntry::make('finance_due_date')->date()->placeholder('-'),
                TextEntry::make('affiliateContact.full_name')->label('Affiliate')->placeholder('-'),
                TextEntry::make('agentContact.full_name')->label('Agent')->placeholder('-'),
                TextEntry::make('comm_in_notes')->placeholder('-')->columnSpanFull(),
                TextEntry::make('comm_out_notes')->placeholder('-')->columnSpanFull(),
                IconEntry::make('is_comments_enabled')->boolean(),
                TextEntry::make('status_updated_at')->dateTime()->placeholder('-'),
                TextEntry::make('created_at')->dateTime(),
                TextEntry::make('updated_at')->dateTime(),
            ]);
    }
}
