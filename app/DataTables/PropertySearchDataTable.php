<?php

declare(strict_types=1);

namespace App\DataTables;

use App\Models\PropertySearch;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Machour\DataTable\AbstractDataTable;
use Machour\DataTable\Columns\ColumnBuilder;
use Machour\DataTable\QuickView;
use Override;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
final class PropertySearchDataTable extends AbstractDataTable
{
    #[Override]
    protected static ?int $defaultPerPage = 25;

    #[Override]
    protected static ?int $maxPerPage = 100;

    public function __construct(
        public int $id,
        public ?int $client_contact_id,
        public ?int $agent_contact_id,
        public ?float $budget_min,
        public ?float $budget_max,
        public ?string $created_at,
    ) {}

    public static function fromModel(PropertySearch $model): self
    {
        return new self(
            id: $model->id,
            client_contact_id: $model->client_contact_id,
            agent_contact_id: $model->agent_contact_id,
            budget_min: is_numeric($model->budget_min) ? (float) $model->budget_min : null,
            budget_max: is_numeric($model->budget_max) ? (float) $model->budget_max : null,
            created_at: $model->created_at?->format('Y-m-d H:i'),
        );
    }

    #[Override]
    public static function tableColumns(): array
    {
        return [
            ColumnBuilder::make('id', 'ID')->sortable()->build(),
            ColumnBuilder::make('client_contact_id', 'Client')->build(),
            ColumnBuilder::make('agent_contact_id', 'Agent')->build(),
            ColumnBuilder::make('budget_min', 'Budget Min')->sortable()->build(),
            ColumnBuilder::make('budget_max', 'Budget Max')->sortable()->build(),
            ColumnBuilder::make('created_at', 'Created')->sortable()->build(),
        ];
    }

    #[Override]
    public static function tableAllowedFilters(): array
    {
        return [];
    }

    #[Override]
    public static function tableQuickViews(): array
    {
        return [
            new QuickView(
                id: 'all',
                label: 'All',
                params: [],
                icon: 'list',
                columns: ['id', 'client_contact_id', 'agent_contact_id', 'budget_min', 'budget_max', 'created_at'],
            ),
        ];
    }

    #[Override]
    public static function tableSoftDeletesEnabled(): bool
    {
        return false;
    }

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
        return PropertySearch::query();
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
}
