<?php

declare(strict_types=1);

namespace App\Filament\Resources\EnterpriseInquiries\Schemas;

use Filament\Forms\Components\Select;
use Filament\Schemas\Schema;

final class EnterpriseInquiryForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('status')
                    ->options([
                        'new' => 'New',
                        'contacted' => 'Contacted',
                        'qualified' => 'Qualified',
                        'closed' => 'Closed',
                    ])
                    ->required(),
            ]);
    }
}
