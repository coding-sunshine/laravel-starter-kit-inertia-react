<?php

declare(strict_types=1);

namespace App\DataTables;

use App\Models\PropertyEnquiry;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Machour\DataTable\AbstractDataTable;
use Machour\DataTable\Columns\ColumnBuilder;
use Machour\DataTable\QuickView;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
final class PropertyEnquiryDataTable extends AbstractDataTable
{
    protected static ?int $defaultPerPage = 25;

    protected static ?int $maxPerPage = 100;

    public function __construct(
        public int $id,
        public string $status,
        public ?int $client_contact_id,
        public ?int $agent_contact_id,
        public ?int $lot_id,
        public ?int $project_id,
        public ?string $created_at,
    ) {}

    public static function fromModel(PropertyEnquiry $model): self
    {
        return new self(
            id: $model->id,
            status: $model->status,
            client_contact_id: $model->client_contact_id,
            agent_contact_id: $model->agent_contact_id,
            lot_id: $model->lot_id,
            project_id: $model->project_id,
            created_at: $model->created_at?->format('Y-m-d H:i'),
        );
    }

    public static function tableColumns(): array
    {
        return [
            ColumnBuilder::make('id', 'ID')->sortable()->build(),
            ColumnBuilder::make('status', 'Status')->sortable()->build(),
            ColumnBuilder::make('client_contact_id', 'Client')->build(),
            ColumnBuilder::make('agent_contact_id', 'Agent')->build(),
            ColumnBuilder::make('lot_id', 'Lot')->build(),
            ColumnBuilder::make('project_id', 'Project')->build(),
            ColumnBuilder::make('created_at', 'Created')->sortable()->build(),
        ];
    }

    public static function tableAllowedFilters(): array
    {
        return [
            AllowedFilter::exact('status'),
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
                columns: ['id', 'status', 'client_contact_id', 'agent_contact_id', 'lot_id', 'project_id', 'created_at'],
            ),
            new QuickView(
                id: 'new',
                label: 'New',
                params: ['filter[status]' => 'new'],
                icon: 'inbox',
                columns: ['id', 'status', 'client_contact_id', 'agent_contact_id', 'lot_id', 'project_id', 'created_at'],
            ),
        ];
    }

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

    public static function tableBaseQuery(): Builder
    {
        return PropertyEnquiry::query();
    }

    public static function tableDefaultSort(): string
    {
        return '-created_at';
    }

    public static function tableAuthorize(string $action, Request $request): bool
    {
        return $request->user() !== null;
    }
}
