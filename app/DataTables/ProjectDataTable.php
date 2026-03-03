<?php

declare(strict_types=1);

namespace App\DataTables;

use App\Models\Project;
use Illuminate\Database\Eloquent\Builder;
use Machour\DataTable\AbstractDataTable;
use Machour\DataTable\Columns\Column;
use Machour\DataTable\QuickView;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
final class ProjectDataTable extends AbstractDataTable
{
    public function __construct(
        public int $id,
        public ?string $title,
        public ?string $stage,
        public ?string $estate,
        public ?string $developer_name,
        public ?string $projecttype_name,
        public ?int $total_lots,
        public ?string $min_price,
        public ?string $max_price,
        public bool $is_archived,
        public ?string $created_at,
    ) {}

    public static function fromModel(Project $model): self
    {
        return new self(
            id: $model->id,
            title: $model->title,
            stage: $model->stage,
            estate: $model->estate,
            developer_name: $model->developer?->legacy_developer_id ? (string) $model->developer->legacy_developer_id : null,
            projecttype_name: $model->projecttype?->name ?? null,
            total_lots: $model->total_lots,
            min_price: $model->min_price !== null ? (string) $model->min_price : null,
            max_price: $model->max_price !== null ? (string) $model->max_price : null,
            is_archived: (bool) $model->is_archived,
            created_at: $model->created_at?->format('Y-m-d H:i'),
        );
    }

    public static function tableColumns(): array
    {
        return [
            new Column(id: 'id', label: 'ID', type: 'number', sortable: true),
            new Column(id: 'title', label: 'Title', type: 'string', sortable: true),
            new Column(id: 'stage', label: 'Stage', type: 'string', sortable: true),
            new Column(id: 'estate', label: 'Estate', type: 'string', sortable: true),
            new Column(id: 'developer_name', label: 'Developer', type: 'string', sortable: false),
            new Column(id: 'projecttype_name', label: 'Type', type: 'string', sortable: false),
            new Column(id: 'total_lots', label: 'Total lots', type: 'number', sortable: true),
            new Column(id: 'min_price', label: 'Min price', type: 'string', sortable: true),
            new Column(id: 'max_price', label: 'Max price', type: 'string', sortable: true),
            new Column(id: 'is_archived', label: 'Archived', type: 'boolean', sortable: true),
            new Column(id: 'created_at', label: 'Created at', type: 'date', sortable: true),
        ];
    }

    public static function tableQuickViews(): array
    {
        return [
            new QuickView(id: 'all', label: 'All', params: []),
            new QuickView(id: 'active', label: 'Active', params: ['filter[is_archived]' => '0']),
            new QuickView(id: 'archived', label: 'Archived', params: ['filter[is_archived]' => '1']),
        ];
    }

    public static function tableBaseQuery(): Builder
    {
        return Project::query()
            ->with(['developer', 'projecttype']);
    }

    public static function tableDefaultSort(): string
    {
        return '-id';
    }
}
