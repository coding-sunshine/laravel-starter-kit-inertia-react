<?php

declare(strict_types=1);

namespace App\DataTables;

use App\Models\Task;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Machour\DataTable\AbstractDataTable;
use Machour\DataTable\Columns\ColumnBuilder;
use Machour\DataTable\Concerns\HasExport;
use Machour\DataTable\QuickView;
use Override;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
final class TaskDataTable extends AbstractDataTable
{
    use HasExport;

    #[Override]
    protected static ?int $defaultPerPage = 25;

    #[Override]
    protected static ?int $maxPerPage = 100;

    public function __construct(
        public int $id,
        public string $title,
        public ?string $contact_name,
        public string $type,
        public string $priority,
        public ?string $due_at,
        public bool $is_completed,
        public ?string $completed_at,
        public ?string $created_at,
    ) {}

    public static function fromModel(Task $model): self
    {
        return new self(
            id: $model->id,
            title: $model->title,
            contact_name: $model->assignedContact?->full_name ?? null,
            type: $model->type,
            priority: $model->priority,
            due_at: $model->due_at?->format('Y-m-d H:i'),
            is_completed: $model->is_completed,
            completed_at: $model->completed_at?->format('Y-m-d H:i'),
            created_at: $model->created_at?->format('Y-m-d H:i'),
        );
    }

    #[Override]
    public static function tableColumns(): array
    {
        return [
            ColumnBuilder::make('id')->label('ID')->sortable(),
            ColumnBuilder::make('title')->label('Title')->sortable()->searchable(),
            ColumnBuilder::make('contact_name')->label('Contact')->searchable(),
            ColumnBuilder::make('type')->label('Type')->sortable(),
            ColumnBuilder::make('priority')->label('Priority')->sortable(),
            ColumnBuilder::make('due_at')->label('Due Date')->sortable(),
            ColumnBuilder::make('is_completed')->label('Completed')->sortable(),
            ColumnBuilder::make('completed_at')->label('Completed At')->sortable(),
            ColumnBuilder::make('created_at')->label('Created')->sortable(),
        ];
    }

    #[Override]
    public static function tableAllowedFilters(): array
    {
        return [
            AllowedFilter::partial('title'),
            AllowedFilter::exact('is_completed'),
            AllowedFilter::exact('priority'),
            AllowedFilter::exact('type'),
            AllowedFilter::exact('status'),
        ];
    }

    #[Override]
    public static function tableQuickViews(): array
    {
        return [
            new QuickView(
                id: 'pending',
                label: 'Pending',
                params: ['filter[is_completed]' => '0'],
                icon: 'clock',
                columns: ['id', 'title', 'contact_name', 'type', 'priority', 'due_at'],
            ),
            new QuickView(
                id: 'completed',
                label: 'Completed',
                params: ['filter[is_completed]' => '1'],
                icon: 'check-circle',
                columns: ['id', 'title', 'contact_name', 'type', 'completed_at'],
            ),
            new QuickView(
                id: 'urgent',
                label: 'Urgent',
                params: ['filter[priority]' => 'urgent'],
                icon: 'alert',
                columns: ['id', 'title', 'contact_name', 'type', 'due_at'],
            ),
        ];
    }

    #[Override]
    public static function tableSoftDeletesEnabled(): bool
    {
        return true;
    }

    #[Override]
    public static function inertiaProps(Request $request): array
    {
        return [
            'tableData' => self::makeTable($request)->toArray(),
            'searchableColumns' => self::tableSearchableColumns(),
        ];
    }

    #[Override]
    public static function tableBaseQuery(): Builder
    {
        return Task::query()->with(['assignedContact']);
    }

    #[Override]
    public static function tableDefaultSort(): string
    {
        return '-created_at';
    }

    #[Override]
    public static function tableAuthorize(string $action, Request $request): bool
    {
        return $request->user() !== null;
    }

    #[Override]
    public static function tableExportName(): string
    {
        return 'tasks';
    }
}
