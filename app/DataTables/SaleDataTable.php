<?php

declare(strict_types=1);

namespace App\DataTables;

use App\Models\Sale;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Machour\DataTable\AbstractDataTable;
use Machour\DataTable\Columns\ColumnBuilder;
use Machour\DataTable\Concerns\HasAi;
use Machour\DataTable\Concerns\HasExport;
use Machour\DataTable\QuickView;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
final class SaleDataTable extends AbstractDataTable
{
    use HasAi;
    use HasExport;

    protected static ?int $defaultPerPage = 25;

    protected static ?int $maxPerPage = 100;

    public function __construct(
        public int $id,
        public string $status,
        public ?float $comms_in_total,
        public ?float $comms_out_total,
        public ?int $lot_id,
        public ?int $project_id,
        public ?int $client_contact_id,
        public ?string $settled_at,
        public ?string $created_at,
    ) {}

    public static function fromModel(Sale $model): self
    {
        return new self(
            id: $model->id,
            status: $model->status,
            comms_in_total: is_numeric($model->comms_in_total) ? (float) $model->comms_in_total : null,
            comms_out_total: is_numeric($model->comms_out_total) ? (float) $model->comms_out_total : null,
            lot_id: $model->lot_id,
            project_id: $model->project_id,
            client_contact_id: $model->client_contact_id,
            settled_at: $model->settled_at?->format('Y-m-d H:i'),
            created_at: $model->created_at?->format('Y-m-d H:i'),
        );
    }

    public static function tableColumns(): array
    {
        return [
            ColumnBuilder::make('id', 'ID')->sortable()->build(),
            ColumnBuilder::make('status', 'Status')->sortable()->build(),
            ColumnBuilder::make('comms_in_total', 'Comms In')->currency('AUD')->sortable()->build(),
            ColumnBuilder::make('comms_out_total', 'Comms Out')->currency('AUD')->sortable()->build(),
            ColumnBuilder::make('lot_id', 'Lot')->build(),
            ColumnBuilder::make('project_id', 'Project')->build(),
            ColumnBuilder::make('client_contact_id', 'Client')->build(),
            ColumnBuilder::make('settled_at', 'Settled At')->sortable()->build(),
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
                columns: ['id', 'status', 'comms_in_total', 'comms_out_total', 'lot_id', 'project_id', 'client_contact_id', 'settled_at', 'created_at'],
            ),
            new QuickView(
                id: 'active',
                label: 'Active',
                params: ['filter[status]' => 'active'],
                icon: 'check-circle',
                columns: ['id', 'status', 'comms_in_total', 'comms_out_total', 'lot_id', 'project_id', 'client_contact_id', 'created_at'],
            ),
            new QuickView(
                id: 'settling_soon',
                label: 'Settling Soon',
                params: ['filter[status]' => 'settling_soon'],
                icon: 'clock',
                columns: ['id', 'status', 'comms_in_total', 'comms_out_total', 'lot_id', 'project_id', 'client_contact_id', 'settled_at'],
            ),
            new QuickView(
                id: 'settled',
                label: 'Settled',
                params: ['filter[status]' => 'settled'],
                icon: 'flag',
                columns: ['id', 'status', 'comms_in_total', 'comms_out_total', 'lot_id', 'project_id', 'client_contact_id', 'settled_at'],
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
        return Sale::query();
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
        return 'sales';
    }

    public static function tableAiSystemContext(): string
    {
        return 'You are analyzing a property sales register for a real estate agency. Sales track completed or in-progress property transactions. Key fields: sale_price, commission_total (sum of all agent commissions), status (state machine), settled_at. Help calculate commission forecasts and identify pipeline value.';
    }
}
