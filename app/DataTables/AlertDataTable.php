<?php

declare(strict_types=1);

namespace App\DataTables;

use App\Models\Alert;
use App\Models\Siding;
use Illuminate\Database\Eloquent\Builder;
use Machour\DataTable\AbstractDataTable;
use Machour\DataTable\Columns\Column;
use Machour\DataTable\Filters\OperatorFilter;
use Machour\DataTable\QuickView;
use Spatie\QueryBuilder\AllowedFilter;

final class AlertDataTable extends AbstractDataTable
{
    public function __construct(
        public int $id,
        public string $type,
        public string $title,
        public ?string $body,
        public string $severity,
        public string $status,
        public string $created_at,
        public ?int $rake_id,
        public ?int $siding_id,
        public ?string $rake_number,
        public ?string $siding_name,
    ) {}

    public static function fromModel(Alert $model): self
    {
        return new self(
            id: $model->id,
            type: $model->type,
            title: $model->title,
            body: $model->body,
            severity: $model->severity,
            status: $model->status,
            created_at: $model->created_at?->toIso8601String() ?? '',
            rake_id: $model->rake_id,
            siding_id: $model->siding_id,
            rake_number: $model->rake?->rake_number,
            siding_name: $model->siding?->name,
        );
    }

    public static function tableColumns(): array
    {
        return [
            new Column(id: 'type', label: 'Type', type: 'text', sortable: true, filterable: true),
            new Column(id: 'title', label: 'Title', type: 'text', sortable: true, filterable: true),
            new Column(id: 'rake_number', label: 'Rake / Siding', type: 'text', sortable: false, filterable: false),
            new Column(id: 'severity', label: 'Severity', type: 'option', sortable: true, filterable: true, options: [
                ['label' => 'Low', 'value' => 'low'],
                ['label' => 'Medium', 'value' => 'medium'],
                ['label' => 'High', 'value' => 'high'],
                ['label' => 'Critical', 'value' => 'critical'],
            ]),
            new Column(id: 'status', label: 'Status', type: 'option', sortable: true, filterable: true, options: [
                ['label' => 'Active', 'value' => 'active'],
                ['label' => 'Resolved', 'value' => 'resolved'],
            ]),
            new Column(id: 'created_at', label: 'Created', type: 'date', sortable: true, filterable: true),
        ];
    }

    public static function tableQuickViews(): array
    {
        return [
            new QuickView(id: 'all', label: 'All', params: []),
            new QuickView(
                id: 'active',
                label: 'Active',
                params: ['filter[status]' => 'eq:active'],
                icon: 'alert-triangle',
            ),
            new QuickView(
                id: 'resolved',
                label: 'Resolved',
                params: ['filter[status]' => 'eq:resolved'],
                icon: 'check',
            ),
        ];
    }

    public static function tableBaseQuery(): Builder
    {
        $user = request()->user();
        $sidingIds = $user && $user->isSuperAdmin()
            ? Siding::query()->pluck('id')->all()
            : ($user ? $user->accessibleSidings()->get()->pluck('id')->all() : []);

        return Alert::query()
            ->with('rake:id,rake_number', 'siding:id,name,code')
            ->forSidings($sidingIds);
    }

    public static function tableDefaultSort(): string
    {
        return '-created_at';
    }

    public static function tableAllowedFilters(): array
    {
        return [
            AllowedFilter::custom('type', new OperatorFilter('text')),
            AllowedFilter::custom('title', new OperatorFilter('text')),
            AllowedFilter::custom('severity', new OperatorFilter('option')),
            AllowedFilter::custom('status', new OperatorFilter('option')),
            AllowedFilter::custom('created_at', new OperatorFilter('date')),
            AllowedFilter::custom('siding_id', new OperatorFilter('number')),
        ];
    }

    public static function tableAllowedSorts(): array
    {
        return ['type', 'title', 'severity', 'status', 'created_at'];
    }
}
