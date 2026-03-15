<?php

declare(strict_types=1);

namespace App\DataTables;

use App\Models\Lot;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Machour\DataTable\AbstractDataTable;
use Machour\DataTable\Columns\ColumnBuilder;
use Machour\DataTable\Concerns\HasAi;
use Machour\DataTable\Concerns\HasExport;
use Machour\DataTable\QuickView;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;
use Throwable;

#[TypeScript]
final class LotDataTable extends AbstractDataTable
{
    use HasAi;
    use HasExport;

    protected static ?int $defaultPerPage = 25;

    protected static ?int $maxPerPage = 100;

    public function __construct(
        public int $id,
        public ?string $title,
        public ?string $project_title,
        public ?string $level,
        public ?int $bedrooms,
        public ?int $bathrooms,
        public ?int $car,
        public ?float $internal,
        public ?float $total,
        public ?float $price,
        public string $title_status,
        public ?float $weekly_rent,
        public bool $is_archived,
        public ?string $image,
        public ?string $created_at,
    ) {}

    public static function fromModel(Lot $model): self
    {
        return new self(
            id: $model->id,
            title: $model->title,
            project_title: $model->relationLoaded('project') ? $model->project?->title : null,
            level: $model->level,
            bedrooms: $model->bedrooms,
            bathrooms: $model->bathrooms,
            car: $model->car,
            internal: is_numeric($model->internal) ? (float) $model->internal : null,
            total: is_numeric($model->total) ? (float) $model->total : null,
            price: is_numeric($model->price) ? (float) $model->price : null,
            title_status: $model->title_status,
            weekly_rent: is_numeric($model->weekly_rent) ? (float) $model->weekly_rent : null,
            is_archived: $model->is_archived,
            image: self::safeProjectMediaUrl($model),
            created_at: $model->created_at?->format('Y-m-d H:i'),
        );
    }

    public static function tableColumns(): array
    {
        return [
            ColumnBuilder::make('image', 'Photo')
                ->image()
                ->build(),
            ColumnBuilder::make('id', 'ID')
                ->number()
                ->sortable()
                ->prefix('#')
                ->build(),
            ColumnBuilder::make('title', 'Lot')
                ->text()
                ->sortable()
                ->filterable()
                ->build(),
            ColumnBuilder::make('project_title', 'Project')
                ->text()
                ->relation('project')
                ->internalName('project.title')
                ->sortable()
                ->filterable()
                ->build(),
            ColumnBuilder::make('level', 'Level')
                ->text()
                ->sortable()
                ->filterable()
                ->build(),
            ColumnBuilder::make('bedrooms', 'Bed')
                ->number()
                ->sortable()
                ->filterable()
                ->build(),
            ColumnBuilder::make('bathrooms', 'Bath')
                ->number()
                ->sortable()
                ->filterable()
                ->build(),
            ColumnBuilder::make('car', 'Car')
                ->number()
                ->sortable()
                ->filterable()
                ->build(),
            ColumnBuilder::make('total', 'Total m²')
                ->number()
                ->sortable()
                ->build(),
            ColumnBuilder::make('price', 'Price')
                ->currency('AUD')
                ->sortable()
                ->build(),
            ColumnBuilder::make('title_status', 'Status')
                ->badge([
                    ['label' => 'Available', 'value' => 'available', 'variant' => 'success'],
                    ['label' => 'Reserved', 'value' => 'reserved', 'variant' => 'secondary'],
                    ['label' => 'Sold', 'value' => 'sold', 'variant' => 'destructive'],
                ])
                ->filterable()
                ->build(),
            ColumnBuilder::make('created_at', 'Created')
                ->date()
                ->sortable()
                ->build(),
        ];
    }

    public static function tableSearchableColumns(): array
    {
        return ['title'];
    }

    public static function tableAllowedFilters(): array
    {
        return [
            AllowedFilter::partial('title'),
            AllowedFilter::exact('title_status'),
            AllowedFilter::exact('is_archived'),
            AllowedFilter::exact('bedrooms'),
            AllowedFilter::exact('bathrooms'),
            AllowedFilter::callback('project_id', fn (Builder $q, $v) => $q->where('project_id', $v)),
        ];
    }

    public static function tableQuickViews(): array
    {
        return [
            new QuickView(
                id: 'available',
                label: 'Available',
                params: ['filter[title_status]' => 'available', 'filter[is_archived]' => '0'],
                icon: 'check-circle',
                columns: ['id', 'title', 'project_title', 'level', 'bedrooms', 'bathrooms', 'car', 'total', 'price'],
            ),
            new QuickView(
                id: 'reserved',
                label: 'Reserved',
                params: ['filter[title_status]' => 'reserved'],
                icon: 'clock',
                columns: ['id', 'title', 'project_title', 'level', 'bedrooms', 'bathrooms', 'price', 'title_status'],
            ),
            new QuickView(
                id: 'sold',
                label: 'Sold',
                params: ['filter[title_status]' => 'sold'],
                icon: 'check',
                columns: ['id', 'title', 'project_title', 'price', 'title_status'],
            ),
            new QuickView(
                id: 'all',
                label: 'All',
                params: [],
                icon: 'list',
                columns: ['id', 'title', 'project_title', 'level', 'bedrooms', 'bathrooms', 'car', 'total', 'price', 'title_status', 'created_at'],
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

    public static function tableDefaultLayout(): string
    {
        return 'cards';
    }

    public static function tableBaseQuery(): Builder
    {
        return Lot::query()->with(['project', 'project.media']);
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
        return 'lots';
    }

    public static function tableAiSystemContext(): string
    {
        return 'You are analyzing a lot/unit inventory for a property development project. Each lot has bed/bath/car counts, size in m², price, and a status (available/reserved/sold). Help agents identify lots that match specific buyer criteria (budget, size, bedrooms).';
    }

    private static function safeProjectMediaUrl(Lot $model): ?string
    {
        if (! $model->relationLoaded('project') || $model->project === null) {
            return null;
        }

        try {
            $url = $model->project->getFirstMediaUrl('photo');

            return $url !== '' ? $url : null;
        } catch (Throwable) {
            return null;
        }
    }
}
