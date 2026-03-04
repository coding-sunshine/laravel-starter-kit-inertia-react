<?php

declare(strict_types=1);

namespace App\Filament\Resources\BrochureProcessings\Tables;

use App\Actions\CreateFromBrochureProcessingAction;
use App\Models\BrochureProcessing;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Textarea;
use Filament\Support\Colors\Color;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

final class BrochureProcessingsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')
                    ->label('ID')
                    ->sortable(),

                TextColumn::make('type')
                    ->label('Type')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'project' => 'info',
                        'lot' => 'success',
                        default => 'gray',
                    }),

                BadgeColumn::make('status')
                    ->label('Status')
                    ->color(fn (string $state): string => match ($state) {
                        'pending_approval' => 'warning',
                        'approved' => 'success',
                        'rejected' => 'danger',
                        'created' => 'primary',
                        default => 'gray',
                    }),

                TextColumn::make('extracted_data.title')
                    ->label('Title')
                    ->limit(50)
                    ->tooltip(fn (?string $state): ?string => $state),

                TextColumn::make('processedByUser.name')
                    ->label('Processed By')
                    ->sortable(),

                TextColumn::make('approvedByUser.name')
                    ->label('Approved By')
                    ->placeholder('—')
                    ->sortable(),

                TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options([
                        'pending_approval' => 'Pending Approval',
                        'approved' => 'Approved',
                        'rejected' => 'Rejected',
                        'created' => 'Created',
                    ]),

                SelectFilter::make('type')
                    ->options([
                        'project' => 'Project',
                        'lot' => 'Lot',
                    ]),
            ])
            ->defaultSort('created_at', 'desc')
            ->recordActions([
                Action::make('approve')
                    ->label('Approve')
                    ->icon(Heroicon::OutlinedCheckCircle)
                    ->color(Color::Green)
                    ->visible(fn (BrochureProcessing $record): bool => $record->isPending())
                    ->form([
                        Textarea::make('admin_notes')
                            ->label('Admin Notes (Optional)')
                            ->placeholder('Add any notes about this approval...')
                            ->rows(3),
                    ])
                    ->action(function (BrochureProcessing $record, array $data): void {
                        $record->update([
                            'status' => 'approved',
                            'approved_by_user_id' => auth()->id(),
                            'approved_at' => now(),
                            'admin_notes' => $data['admin_notes'] ?? null,
                        ]);
                    })
                    ->successNotificationTitle('Brochure processing approved!'),

                Action::make('reject')
                    ->label('Reject')
                    ->icon(Heroicon::OutlinedXCircle)
                    ->color(Color::Red)
                    ->visible(fn (BrochureProcessing $record): bool => $record->isPending())
                    ->requiresConfirmation()
                    ->modalHeading('Reject Brochure Processing')
                    ->modalDescription('Are you sure you want to reject this brochure processing?')
                    ->form([
                        Textarea::make('admin_notes')
                            ->label('Rejection Reason')
                            ->placeholder('Why are you rejecting this processing?')
                            ->required()
                            ->rows(3),
                    ])
                    ->action(function (BrochureProcessing $record, array $data): void {
                        $record->update([
                            'status' => 'rejected',
                            'approved_by_user_id' => auth()->id(),
                            'approved_at' => now(),
                            'admin_notes' => $data['admin_notes'],
                        ]);
                    })
                    ->successNotificationTitle('Brochure processing rejected.'),

                Action::make('create_record')
                    ->label('Create Record')
                    ->icon(Heroicon::OutlinedPlus)
                    ->color(Color::Blue)
                    ->visible(fn (BrochureProcessing $record): bool => $record->isApproved())
                    ->requiresConfirmation()
                    ->modalHeading(fn (BrochureProcessing $record): string => "Create {$record->type} from extracted data")
                    ->modalDescription('This will create a new record in the system using the extracted data.')
                    ->action(function (BrochureProcessing $record): void {
                        $action = new CreateFromBrochureProcessingAction();
                        $createdRecord = $action->handle($record);

                        $type = $record->type;
                        $title = $createdRecord->title ?? 'record';

                        // Send success notification
                        \Filament\Notifications\Notification::make()
                            ->title("✅ {$type} created successfully!")
                            ->body("Created {$type}: {$title}")
                            ->success()
                            ->send();
                    })
                    ->successNotificationTitle(fn (BrochureProcessing $record): string =>
                        ucfirst($record->type) . ' created successfully!'
                    ),

                EditAction::make()
                    ->visible(fn (BrochureProcessing $record): bool => !$record->isCreated()),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
