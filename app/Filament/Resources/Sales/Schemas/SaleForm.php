<?php

declare(strict_types=1);

namespace App\Filament\Resources\Sales\Schemas;

use App\Models\Contact;
use App\Models\Developer;
use App\Models\Lot;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

final class SaleForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('client_contact_id')
                    ->label('Client contact')
                    ->relationship('clientContact', 'first_name', fn ($q) => $q->orderBy('first_name')->orderBy('last_name'))
                    ->getOptionLabelFromRecordUsing(fn (Contact $c) => $c->full_name)
                    ->searchable(['first_name', 'last_name']),
                Select::make('project_id')
                    ->relationship('project', 'title')
                    ->searchable()
                    ->preload(),
                Select::make('lot_id')
                    ->relationship('lot', 'id')
                    ->getOptionLabelFromRecordUsing(fn (Lot $lot) => (string) ($lot->legacy_lot_id ?? $lot->id))
                    ->searchable(),
                Select::make('developer_id')
                    ->relationship('developer', 'legacy_developer_id')
                    ->getOptionLabelFromRecordUsing(fn (Developer $d) => (string) ($d->legacy_developer_id ?? $d->id))
                    ->searchable(),
                Select::make('affiliate_contact_id')
                    ->label('Affiliate contact')
                    ->relationship('affiliateContact', 'first_name', fn ($q) => $q->orderBy('first_name')->orderBy('last_name'))
                    ->getOptionLabelFromRecordUsing(fn (Contact $c) => $c->full_name)
                    ->searchable(['first_name', 'last_name']),
                Select::make('agent_contact_id')
                    ->label('Agent contact')
                    ->relationship('agentContact', 'first_name', fn ($q) => $q->orderBy('first_name')->orderBy('last_name'))
                    ->getOptionLabelFromRecordUsing(fn (Contact $c) => $c->full_name)
                    ->searchable(['first_name', 'last_name']),
                TextInput::make('comms_in_total')->numeric()->prefix('$'),
                TextInput::make('comms_out_total')->numeric()->prefix('$'),
                DatePicker::make('finance_due_date'),
                Textarea::make('comm_in_notes')->rows(2),
                Textarea::make('comm_out_notes')->rows(2),
                Toggle::make('is_comments_enabled'),
            ]);
    }
}
