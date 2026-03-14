<?php

declare(strict_types=1);

namespace App\DataTables;

use App\Models\Sale;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Machour\DataTable\AbstractDataTable;
use Machour\DataTable\Columns\ColumnBuilder;
use Machour\DataTable\Concerns\HasExport;
use Machour\DataTable\QuickView;
use Override;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
final class SaleDataTable extends AbstractDataTable
{
    use HasExport;

    #[Override]
    protected static ?int $defaultPerPage = 25;

    #[Override]
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
            comms_in_total: $model->comms_in_total,
            comms_out_total: $model->comms_out_total,
            lot_id: $model->lot_id,
            project_id: $model->project_id,
            client_contact_id: $model->client_contact_id,
            settled_at: $model->settled_at?->format('Y-m-d H:i'),
            created_at: $model->created_at?->format('Y-m-d H:i'),
        );
    }

    #[Override]
    public static function tableColumns(): array
    {
        return [
            ColumnBuilder::make('id')->label('ID')->sortable(),
            ColumnBuilder::make('status')->label('Status')->sortable(),
            ColumnBuilder::make('comms_in_total')->label('Comms In')->sortable(),
            ColumnBuilder::make('comms_out_total')->label('Comms Out')->sortable(),
            ColumnBuilder::make('lot_id')->label('Lot'),
            ColumnBuilder::make('project_id')->label('Project'),
            ColumnBuilder::make('client_contact_id')->label('Client'),
            ColumnBuilder::make('settled_at')->label('Settled At')->sortable(),
            ColumnBuilder::make('created_at')->label('Created')->sortable(),
        ];
    }

    #[Override]
    public static function tableAllowedFilters(): array
    {
        return [
            AllowedFilter::exact('status'),
        ];
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

    #[Override]
    public static function tableSoftDeletesEnabled(): bool
    {
        return true;
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
        return Sale::query();
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

    #[Override]
    public static function tableExportName(): string
    {
        return 'sales';
    }
}
