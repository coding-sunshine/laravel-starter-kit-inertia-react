<?php

declare(strict_types=1);

namespace App\DataTables;

use App\Models\Project;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Machour\DataTable\AbstractDataTable;
use Machour\DataTable\Columns\ColumnBuilder;
use Machour\DataTable\Concerns\HasAi;
use Machour\DataTable\Concerns\HasExport;
use Machour\DataTable\QuickView;
use Override;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
final class ProjectDataTable extends AbstractDataTable
{
    use HasAi;
    use HasExport;

    #[Override]
    protected static ?int $defaultPerPage = 25;

    #[Override]
    protected static ?int $maxPerPage = 100;

    public function __construct(
        public int $id,
        public string $title,
        public string $stage,
        public ?string $suburb,
        public ?string $state,
        public ?string $developer_name,
        public ?float $min_price,
        public ?float $max_price,
        public ?int $total_lots,
        public bool $is_hot_property,
        public bool $is_featured,
        public bool $is_archived,
        public ?string $created_at,
    ) {}

    public static function fromModel(Project $model): self
    {
        return new self(
            id: $model->id,
            title: $model->title,
            stage: $model->stage,
            suburb: $model->suburb,
            state: $model->state,
            developer_name: $model->relationLoaded('developer') ? $model->developer?->name : null,
            min_price: $model->min_price !== null ? (float) $model->min_price : null,
            max_price: $model->max_price !== null ? (float) $model->max_price : null,
            total_lots: $model->total_lots,
            is_hot_property: $model->is_hot_property,
            is_featured: $model->is_featured,
            is_archived: $model->is_archived,
            created_at: $model->created_at?->format('Y-m-d H:i'),
        );
    }

    public static function tableColumns(): array
    {
        return [
            ColumnBuilder::make('id', 'ID')
                ->number()
                ->sortable()
                ->prefix('#')
                ->build(),
            ColumnBuilder::make('title', 'Project Name')
                ->text()
                ->sortable()
                ->filterable()
                ->build(),
            ColumnBuilder::make('stage', 'Stage')
                ->badge([
                    ['label' => 'Pre-Launch', 'value' => 'pre_launch', 'variant' => 'secondary'],
                    ['label' => 'Selling', 'value' => 'selling', 'variant' => 'success'],
                    ['label' => 'Completed', 'value' => 'completed', 'variant' => 'default'],
                    ['label' => 'Archived', 'value' => 'archived', 'variant' => 'destructive'],
                ])
                ->filterable()
                ->build(),
            ColumnBuilder::make('suburb', 'Suburb')
                ->text()
                ->sortable()
                ->filterable()
                ->build(),
            ColumnBuilder::make('state', 'State')
                ->text()
                ->sortable()
                ->filterable()
                ->build(),
            ColumnBuilder::make('developer_name', 'Developer')
                ->text()
                ->relation('developer')
                ->internalName('developer.name')
                ->sortable()
                ->build(),
            ColumnBuilder::make('min_price', 'From Price')
                ->money()
                ->sortable()
                ->build(),
            ColumnBuilder::make('total_lots', 'Total Lots')
                ->number()
                ->sortable()
                ->build(),
            ColumnBuilder::make('is_hot_property', 'Hot')
                ->badge([
                    ['label' => 'Hot', 'value' => '1', 'variant' => 'destructive'],
                    ['label' => 'Normal', 'value' => '0', 'variant' => 'secondary'],
                ])
                ->filterable()
                ->build(),
            ColumnBuilder::make('is_archived', 'Archived')
                ->badge([
                    ['label' => 'Active', 'value' => '0', 'variant' => 'success'],
                    ['label' => 'Archived', 'value' => '1', 'variant' => 'secondary'],
                ])
                ->filterable()
                ->build(),
            ColumnBuilder::make('created_at', 'Created')
                ->date()
                ->sortable()
                ->filterable()
                ->build(),
        ];
    }

    public static function tableSearchableColumns(): array
    {
        return ['title', 'suburb'];
    }

    public static function tableAllowedFilters(): array
    {
        return [
            AllowedFilter::partial('title'),
            AllowedFilter::partial('suburb'),
            AllowedFilter::exact('stage'),
            AllowedFilter::exact('state'),
            AllowedFilter::exact('is_hot_property'),
            AllowedFilter::exact('is_archived'),
            AllowedFilter::exact('is_featured'),
        ];
    }

    public static function tableQuickViews(): array
    {
        return [
            new QuickView(
                id: 'all',
                label: 'All',
                params: [],
                icon: 'list',
                columns: ['id', 'title', 'stage', 'suburb', 'state', 'developer_name', 'min_price', 'total_lots', 'is_hot_property', 'created_at'],
            ),
            new QuickView(
                id: 'available',
                label: 'Available',
                params: ['filter[is_archived]' => '0'],
                icon: 'check-circle',
                columns: ['id', 'title', 'stage', 'suburb', 'state', 'developer_name', 'min_price', 'total_lots', 'created_at'],
            ),
            new QuickView(
                id: 'hot',
                label: 'Hot Properties',
                params: ['filter[is_hot_property]' => '1'],
                icon: 'flame',
                columns: ['id', 'title', 'stage', 'suburb', 'state', 'developer_name', 'min_price'],
            ),
            new QuickView(
                id: 'pre_launch',
                label: 'Pre-Launch',
                params: ['filter[stage]' => 'pre_launch'],
                icon: 'rocket',
                columns: ['id', 'title', 'suburb', 'state', 'developer_name', 'min_price'],
            ),
        ];
    }

    public static function tableSoftDeletesEnabled(): bool
    {
        return true;
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
        return Project::query()->with('developer');
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
        return 'projects';
    }

    public static function tableAiSystemContext(): string
    {
        return 'You are analyzing a real estate project inventory for a property sales agency. Projects are residential developments (apartments, houses, land). Key fields: stage (pre_launch/selling/completed), available/reserved/sold lot counts, price range, developer, suburb/state. Help agents identify projects to present to specific buyer profiles.';
    }
}
