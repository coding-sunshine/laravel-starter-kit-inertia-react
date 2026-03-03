<?php

declare(strict_types=1);

namespace App\DataTables;

use App\Models\Task;
use Illuminate\Database\Eloquent\Builder;
use Machour\DataTable\AbstractDataTable;
use Machour\DataTable\Columns\Column;
use Machour\DataTable\QuickView;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
final class TaskDataTable extends AbstractDataTable
{
    public function __construct(
        public int $id,
        public ?string $title,
        public ?string $status,
        public ?string $priority,
        public ?string $due_at,
        public ?string $assigned_contact_name,
        public ?string $assigned_user_name,
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
            due_at: $model->due_at?->format('Y-m-d'),
            assigned_contact_name: $model->assignedContact?->full_name,
            assigned_user_name: $model->assignedUser?->name,
            completed_at: $model->completed_at?->format('Y-m-d H:i'),
            created_at: $model->created_at?->format('Y-m-d H:i'),
        );
    }

    public static function tableColumns(): array
    {
        return [
            new Column(id: 'id', label: 'ID', type: 'number', sortable: true),
            new Column(id: 'title', label: 'Title', type: 'string', sortable: true),
            new Column(id: 'status', label: 'Status', type: 'string', sortable: true),
            new Column(id: 'priority', label: 'Priority', type: 'string', sortable: true),
            new Column(id: 'due_at', label: 'Due', type: 'date', sortable: true),
            new Column(id: 'assigned_contact_name', label: 'Assigned contact', type: 'string', sortable: false),
            new Column(id: 'assigned_user_name', label: 'Assigned to', type: 'string', sortable: false),
            new Column(id: 'completed_at', label: 'Completed', type: 'date', sortable: true),
            new Column(id: 'created_at', label: 'Created at', type: 'date', sortable: true),
        ];
    }

    public static function tableQuickViews(): array
    {
        return [
            new QuickView(id: 'all', label: 'All', params: []),
            new QuickView(id: 'open', label: 'Open', params: ['filter' => 'open']),
            new QuickView(id: 'completed', label: 'Completed', params: ['filter' => 'completed']),
            new QuickView(id: 'overdue', label: 'Overdue', params: ['filter' => 'overdue']),
        ];
    }

    public static function tableBaseQuery(): Builder
    {
        return Task::query()
            ->with(['assignedContact', 'assignedUser'])
            ->when(request('filter') === 'open', fn (Builder $q) => $q->whereNull('completed_at'))
            ->when(request('filter') === 'completed', fn (Builder $q) => $q->whereNotNull('completed_at'))
            ->when(request('filter') === 'overdue', fn (Builder $q) => $q->whereNull('completed_at')->where('due_at', '<', now()));
    }

    public static function tableDefaultSort(): string
    {
        return '-id';
    }
}
