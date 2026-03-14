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
            budget_min: $model->budget_min,
            budget_max: $model->budget_max,
            created_at: $model->created_at?->format('Y-m-d H:i'),
        );
    }

    #[Override]
    public static function tableColumns(): array
    {
        return [
            ColumnBuilder::make('id')->label('ID')->sortable(),
            ColumnBuilder::make('client_contact_id')->label('Client'),
            ColumnBuilder::make('agent_contact_id')->label('Agent'),
            ColumnBuilder::make('budget_min')->label('Budget Min')->sortable(),
            ColumnBuilder::make('budget_max')->label('Budget Max')->sortable(),
            ColumnBuilder::make('created_at')->label('Created')->sortable(),
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
