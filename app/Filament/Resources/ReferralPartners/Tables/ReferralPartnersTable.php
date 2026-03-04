<?php

declare(strict_types=1);

namespace App\Filament\Resources\ReferralPartners\Tables;

use App\Filament\Concerns\HasStandardExports;
use App\Models\Partner;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

final class ReferralPartnersTable
{
    use HasStandardExports;

    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->defaultPaginationPageOption(10)
            ->paginationPageOptions([10, 25, 50])
            ->searchDebounce('300ms')
            ->filters([
                SelectFilter::make('status')
                    ->options(fn () => Partner::query()->whereNotNull('status')->where('status', '!=', '')->distinct()->pluck('status', 'status')->all())
                    ->searchable(),
            ])
            ->columns([
                TextColumn::make('id')->numeric()->sortable(),
                TextColumn::make('contact.full_name')->label('Contact')->searchable(['first_name', 'last_name']),
                TextColumn::make('contact.company_name')->label('Company')->searchable()->limit(30)->placeholder('—'),
                TextColumn::make('role')->badge()->sortable()->placeholder('—'),
                TextColumn::make('status')->badge()->sortable()->placeholder('—'),
                TextColumn::make('created_at')->dateTime()->sortable()->toggleable(isToggledHiddenByDefault: true),
            ])
            ->headerActions([
                self::makeExportHeaderAction('referral-partners'),
            ])
            ->recordActions([
                ViewAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    self::makeExportBulkAction(),
                ]),
            ]);
    }
}
