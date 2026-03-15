<?php

declare(strict_types=1);

namespace App\DataTables;

use App\Models\Task;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Machour\DataTable\AbstractDataTable;
use Machour\DataTable\Columns\ColumnBuilder;
use Machour\DataTable\Concerns\HasAi;
use Machour\DataTable\Concerns\HasExport;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
final class TaskReportDataTable extends AbstractDataTable
{
    use HasAi;
    use HasExport;

    protected static ?int $defaultPerPage = 25;

    public function __construct(
        public int $id,
        public string $title,
        public ?string $status,
        public ?string $priority,
        public ?string $assigned_to_name,
        public ?string $contact_name,
        public ?string $due_at,
        public ?string $completed_at,
        public ?string $created_at,
    ) {}

    public static function fromModel(Task $model): self
    {
        return new self(
            id: $model->id,
            title: $model->title,
            status: $model->status,
            priority: $model->priority,
            assigned_to_name: $model->assignedToUser?->name,
            contact_name: $model->assignedContact ? ($model->assignedContact->first_name.' '.$model->assignedContact->last_name) : null,
            due_at: $model->due_at?->format('Y-m-d'),
            completed_at: $model->completed_at?->format('Y-m-d H:i'),
            created_at: $model->created_at?->format('Y-m-d'),
        );
    }

    public static function tableColumns(): array
    {
        return [
            ColumnBuilder::make('id', 'ID')->sortable()->build(),
            ColumnBuilder::make('title', 'Title')->sortable()->build(),
            ColumnBuilder::make('status', 'Status')->sortable()->build(),
            ColumnBuilder::make('priority', 'Priority')->sortable()->build(),
            ColumnBuilder::make('assigned_to_name', 'Assigned To')->build(),
            ColumnBuilder::make('contact_name', 'Contact')->build(),
            ColumnBuilder::make('due_at', 'Due Date')->sortable()->build(),
            ColumnBuilder::make('completed_at', 'Completed')->sortable()->build(),
            ColumnBuilder::make('created_at', 'Created')->sortable()->build(),
        ];
    }

    public static function tableAllowedFilters(): array
    {
        return [
            AllowedFilter::exact('status'),
            AllowedFilter::exact('priority'),
        ];
    }

    public static function inertiaProps(Request $request): array
    {
        return [
            'tableData' => self::makeTable($request)->toArray(),
            'searchableColumns' => self::tableSearchableColumns(),
        ];
    }

    public static function tableBaseQuery(): Builder
    {
        return Task::query()->with(['assignedToUser', 'assignedContact']);
    }

    public static function tableDefaultSort(): string
    {
        return '-created_at';
    }

    public static function tableAuthorize(string $action, Request $request): bool
    {
        return $request->user() !== null;
    }

    public static function tableExportName(): string
    {
        return 'tasks-report';
    }

    public static function tableAiSystemContext(): string
    {
        return 'You are analyzing a CRM task report. Key fields: status (pending/in_progress/done), priority (low/medium/high/urgent), due_at, completed_at. Help identify overdue tasks, completion rates, and agent workload distribution.';
    }
}
