<?php

declare(strict_types=1);

namespace App\Filament\Pages;

use App\Jobs\ProcessDocumentBatchJob;
use App\Models\BrochureProcessing;
use App\Services\TenantContext;
use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Schemas\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Actions\BulkAction;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Bus\Batch;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Throwable;
use UnitEnum;
use BackedEnum;

final class BulkDocumentProcessing extends Page implements HasTable
{
    use InteractsWithTable;

    public ?string $selectedBatchId = null;

    public array $uploadedFiles = [];

    public string $processingType = 'auto';

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-document-duplicate';

    protected static string|UnitEnum|null $navigationGroup = 'Bot Management';



    protected static ?string $title = 'Bulk Document Processing';

    protected static ?string $navigationLabel = 'Bulk Document Processing';

    public function table(Table $table): Table
    {
        return $table
            ->query($this->getTableQuery())
            ->columns([
                TextColumn::make('batch_id')
                    ->label('Batch ID')
                    ->searchable()
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('file_name')
                    ->label('File Name')
                    ->searchable(query: function (Builder $query, string $search): Builder {
                        return $query->where('file_path', 'like', "%{$search}%");
                    })
                    ->limit(30),

                TextColumn::make('formatted_file_size')
                    ->label('File Size'),

                BadgeColumn::make('type')
                    ->label('Type')
                    ->colors([
                        'success' => 'project',
                        'primary' => 'lot',
                    ]),

                BadgeColumn::make('queue_status')
                    ->label('Queue Status')
                    ->colors([
                        'warning' => 'pending',
                        'primary' => 'processing',
                        'success' => 'completed',
                        'danger' => 'failed',
                    ]),

                BadgeColumn::make('status')
                    ->label('Approval Status')
                    ->colors([
                        'warning' => 'pending_approval',
                        'success' => 'approved',
                        'danger' => 'rejected',
                        'gray' => 'created',
                    ]),

                TextColumn::make('processing_started_at')
                    ->label('Started At')
                    ->dateTime()
                    ->since()
                    ->toggleable(),

                TextColumn::make('processing_completed_at')
                    ->label('Completed At')
                    ->dateTime()
                    ->since()
                    ->toggleable(),

                TextColumn::make('processedByUser.name')
                    ->label('Processed By')
                    ->toggleable(),
            ])
            ->filters([
                SelectFilter::make('queue_status')
                    ->options([
                        'pending' => 'Pending',
                        'processing' => 'Processing',
                        'completed' => 'Completed',
                        'failed' => 'Failed',
                    ]),

                SelectFilter::make('status')
                    ->label('Approval Status')
                    ->options([
                        'pending_approval' => 'Pending Approval',
                        'approved' => 'Approved',
                        'rejected' => 'Rejected',
                        'created' => 'Created',
                    ]),

                SelectFilter::make('type')
                    ->options([
                        'project' => 'Project',
                        'lot' => 'Lot/Property',
                    ]),
            ])
            ->actions([
                Action::make('review')
                    ->label('Review')
                    ->icon('heroicon-o-eye')
                    ->color('info')
                    ->modalHeading(fn ($record) => "Review Extracted Data - {$record->file_name}")
                    ->modalContent(fn ($record) => view('filament.modals.review-extracted-data', ['record' => $record]))
                    ->modalWidth('6xl')
                    ->visible(fn ($record) => $record->status === 'pending_approval'),

                Action::make('approve')
                    ->label('Approve & Create')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalHeading('Create Project/Lot')
                    ->modalDescription(fn ($record) => "This will create a new {$record->type} from the extracted data. Are you sure?")
                    ->action(function ($record): void {
                        $this->approveAndCreate($record);
                    })
                    ->visible(fn ($record) => $record->status === 'pending_approval' && $record->queue_status === 'completed'),

                Action::make('reject')
                    ->label('Reject')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalHeading('Reject Document')
                    ->modalDescription('This will mark the document as rejected. Are you sure?')
                    ->action(function ($record): void {
                        $this->rejectDocument($record);
                    })
                    ->visible(fn ($record) => $record->status === 'pending_approval'),
            ])
            ->bulkActions([
                BulkAction::make('approve')
                    ->label('Bulk Approve')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->action(function (Collection $records): void {
                        $this->bulkApprove($records);
                    })
                    ->requiresConfirmation()
                    ->deselectRecordsAfterCompletion(),

                BulkAction::make('reject')
                    ->label('Bulk Reject')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->action(function (Collection $records): void {
                        $this->bulkReject($records);
                    })
                    ->requiresConfirmation()
                    ->deselectRecordsAfterCompletion(),

                BulkAction::make('reprocess')
                    ->label('Reprocess Failed')
                    ->icon('heroicon-o-arrow-path')
                    ->color('warning')
                    ->action(function (Collection $records): void {
                        $this->reprocessFailed($records);
                    })
                    ->visible(fn (): bool => $this->getTableQuery()->where('queue_status', 'failed')->exists())
                    ->requiresConfirmation()
                    ->deselectRecordsAfterCompletion(),
            ])
            ->defaultSort('created_at', 'desc')
            ->poll('5s'); // Auto-refresh every 5 seconds for real-time updates
    }

    public function mount(): void
    {
        $this->selectedBatchId = request()->query('batch');
    }

    public function getTitle(): string
    {
        if ($this->selectedBatchId && $this->selectedBatchId !== 'all') {
            return "Batch Processing: {$this->selectedBatchId}";
        }

        return 'Bulk Document Processing';
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('upload')
                ->label('Upload Documents')
                ->icon('heroicon-o-cloud-arrow-up')
                ->modalHeading('Upload Multiple Documents')
                ->modalWidth('4xl')
                ->form([
                    Section::make('Document Upload')
                        ->description('Upload multiple PDF, image, or document files for batch processing')
                        ->schema([
                            FileUpload::make('files')
                                ->label('Documents')
                                ->multiple()
                                ->acceptedFileTypes([
                                    'application/pdf',
                                    'image/*',
                                    'application/msword',
                                    'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                                    'text/plain',
                                ])
                                ->maxSize(10240) // 10MB
                                ->maxFiles(20)
                                ->disk('local')
                                ->directory('document-uploads')
                                ->visibility('private')
                                ->required(),

                            Select::make('processing_type')
                                ->label('Processing Type')
                                ->options([
                                    'project' => 'Project Information',
                                    'lot' => 'Lot/Property Information',
                                ])
                                ->default('project')
                                ->required(),
                        ]),
                ])
                ->action(function (array $data): void {
                    $this->processUploadedFiles($data['files'], $data['processing_type']);
                }),

            Action::make('viewBatches')
                ->label('View All Batches')
                ->icon('heroicon-o-queue-list')
                ->url(fn (): string => self::getUrl(['batch' => 'all'])),
        ];
    }

    protected function getTableQuery(): Builder
    {
        $query = BrochureProcessing::query()
            ->with(['processedByUser'])
            ->where('organization_id', TenantContext::id());

        if ($this->selectedBatchId && $this->selectedBatchId !== 'all') {
            $query->where('batch_id', $this->selectedBatchId);
        } elseif (! $this->selectedBatchId) {
            // Show recent uploads (both batch and individual)
            $query->where('created_at', '>=', now()->subDays(7));
        }

        return $query;
    }

    protected function processUploadedFiles(array $files, string $processingType): void
    {
        try {
            $batchId = Str::uuid()->toString();
            $user = auth()->user();
            $organizationId = TenantContext::id();
            $jobs = [];

            DB::transaction(function () use ($files, $batchId, $processingType, $user, $organizationId, &$jobs) {
                foreach ($files as $file) {
                    if (! ($file instanceof UploadedFile)) {
                        continue;
                    }

                    // Store the file
                    $filePath = $file->store('document-uploads', 'local');

                    // Create the processing record
                    $processing = BrochureProcessing::create([
                        'organization_id' => $organizationId,
                        'batch_id' => $batchId,
                        'file_path' => $filePath,
                        'type' => $processingType,
                        'status' => 'pending_approval',
                        'queue_status' => 'pending',
                        'processed_by_user_id' => $user->id,
                        'extracted_data' => [],
                    ]);

                    // Create the job
                    $jobs[] = new ProcessDocumentBatchJob(
                        $processing->id,
                        $organizationId,
                        $user->id
                    );
                }
            });

            // Dispatch the batch
            $batch = Bus::batch($jobs)
                ->name("Document Processing Batch: {$batchId}")
                ->allowFailures()
                ->dispatch();

            $this->selectedBatchId = $batchId;

            Notification::make()
                ->title('Documents Uploaded Successfully')
                ->body(sprintf('%d documents have been queued for processing.', count($files)))
                ->success()
                ->send();

        } catch (Throwable $e) {
            Notification::make()
                ->title('Upload Failed')
                ->body('An error occurred while uploading documents: '.$e->getMessage())
                ->danger()
                ->send();
        }
    }

    protected function bulkApprove(Collection $records): void
    {
        $approved = 0;
        $user = auth()->user();

        foreach ($records as $record) {
            if ($record->queue_status === 'completed' && $record->status === 'pending_approval') {
                $record->update([
                    'status' => 'approved',
                    'approved_by_user_id' => $user->id,
                    'approved_at' => now(),
                ]);
                $approved++;
            }
        }

        Notification::make()
            ->title('Bulk Approval Complete')
            ->body("{$approved} documents have been approved.")
            ->success()
            ->send();
    }

    protected function bulkReject(Collection $records): void
    {
        $rejected = 0;
        $user = auth()->user();

        foreach ($records as $record) {
            if ($record->queue_status === 'completed' && $record->status === 'pending_approval') {
                $record->update([
                    'status' => 'rejected',
                    'approved_by_user_id' => $user->id,
                    'approved_at' => now(),
                ]);
                $rejected++;
            }
        }

        Notification::make()
            ->title('Bulk Rejection Complete')
            ->body("{$rejected} documents have been rejected.")
            ->success()
            ->send();
    }

    protected function reprocessFailed(Collection $records): void
    {
        $reprocessed = 0;
        $user = auth()->user();
        $organizationId = TenantContext::id();
        $jobs = [];

        foreach ($records as $record) {
            if ($record->queue_status === 'failed') {
                $record->update([
                    'queue_status' => 'pending',
                    'processing_started_at' => null,
                    'processing_completed_at' => null,
                    'admin_notes' => null,
                ]);

                $jobs[] = new ProcessDocumentBatchJob(
                    $record->id,
                    $organizationId,
                    $user->id
                );
                $reprocessed++;
            }
        }

        if (! empty($jobs)) {
            Bus::batch($jobs)
                ->name('Reprocess Failed Documents')
                ->allowFailures()
                ->dispatch();
        }

        Notification::make()
            ->title('Reprocessing Started')
            ->body("{$reprocessed} failed documents have been queued for reprocessing.")
            ->success()
            ->send();
    }

    public function getHeaderWidgets(): array
    {
        return [
            // Add statistics widgets here later if needed
        ];
    }

    protected string $view = 'filament.pages.simple-table-page';

    public function approveAndCreate(BrochureProcessing $record): void
    {
        try {
            $user = auth()->user();
            $organizationId = TenantContext::id();

            DB::transaction(function () use ($record, $user, $organizationId) {
                if ($record->type === 'project') {
                    $this->createProjectFromRecord($record, $organizationId, $user);
                } else {
                    $this->createLotFromRecord($record, $organizationId, $user);
                }

                $record->update([
                    'status' => 'approved',
                    'approved_by_user_id' => $user->id,
                    'approved_at' => now(),
                ]);
            });

            Notification::make()
                ->title('Document Approved')
                ->body("Successfully created {$record->type} from extracted data.")
                ->success()
                ->send();

        } catch (Throwable $e) {
            Notification::make()
                ->title('Approval Failed')
                ->body("Failed to create {$record->type}: {$e->getMessage()}")
                ->danger()
                ->send();
        }
    }

    public function rejectDocument(BrochureProcessing $record): void
    {
        $user = auth()->user();

        $record->update([
            'status' => 'rejected',
            'approved_by_user_id' => $user->id,
            'approved_at' => now(),
        ]);

        Notification::make()
            ->title('Document Rejected')
            ->body('The document has been marked as rejected.')
            ->success()
            ->send();
    }

    private function createProjectFromRecord(BrochureProcessing $record, int $organizationId, $user): void
    {
        $data = $record->extracted_data;

        // Create the project using extracted data
        $project = \App\Models\Project::create([
            'organization_id' => $organizationId,
            'title' => $data['title'] ?? 'Untitled Project',
            'estate' => $data['estate'] ?? null,
            'stage' => $data['stage'] ?? null,
            'description' => $data['description'] ?? null,
            'total_lots' => $data['total_lots'] ?? null,
            'min_price' => $data['min_price'] ?? null,
            'max_price' => $data['max_price'] ?? null,
            // Map developer to developer_id if needed, or store as additional field
        ]);

        $record->update(['created_project_id' => $project->id]);
    }

    private function createLotFromRecord(BrochureProcessing $record, int $organizationId, $user): void
    {
        $data = $record->extracted_data;

        // Create the lot using extracted data
        $lot = \App\Models\Lot::create([
            'title' => $data['title'] ?? 'Untitled Lot',
            'price' => $data['price'] ?? null,
            'land_price' => $data['land_price'] ?? null,
            'stage' => $data['stage'] ?? null,
            'bedrooms' => $data['bedrooms'] ?? null,
            'bathrooms' => $data['bathrooms'] ?? null,
            'land_size' => $data['land_size'] ?? null,
            // Note: project_title is stored in extracted data but lots link to projects via project_id
            // For now, we'll create standalone lots. Later we can add project linking logic.
        ]);

        $record->update(['created_lot_id' => $lot->id]);
    }
}
