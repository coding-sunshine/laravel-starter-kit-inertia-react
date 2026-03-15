<?php

declare(strict_types=1);

namespace App\DataTables;

use App\Models\PropertyReservation;
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
final class PropertyReservationDataTable extends AbstractDataTable
{
    use HasAi;
    use HasExport;

    #[Override]
    protected static ?int $defaultPerPage = 25;

    #[Override]
    protected static ?int $maxPerPage = 100;

    public function __construct(
        public int $id,
        public string $stage,
        public string $deposit_status,
        public ?float $purchase_price,
        public ?int $lot_id,
        public ?int $project_id,
        public ?int $agent_contact_id,
        public ?int $primary_contact_id,
        public ?string $created_at,
    ) {}

    public static function fromModel(PropertyReservation $model): self
    {
        return new self(
            id: $model->id,
            stage: $model->stage,
            deposit_status: $model->deposit_status,
            purchase_price: is_numeric($model->purchase_price) ? (float) $model->purchase_price : null,
            lot_id: $model->lot_id,
            project_id: $model->project_id,
            agent_contact_id: $model->agent_contact_id,
            primary_contact_id: $model->primary_contact_id,
            created_at: $model->created_at?->format('Y-m-d H:i'),
        );
    }

    #[Override]
    public static function tableColumns(): array
    {
        return [
            ColumnBuilder::make('id', 'ID')->sortable()->build(),
            ColumnBuilder::make('stage', 'Stage')->sortable()->build(),
            ColumnBuilder::make('deposit_status', 'Deposit Status')->sortable()->build(),
            ColumnBuilder::make('purchase_price', 'Purchase Price')->currency('AUD')->sortable()->build(),
            ColumnBuilder::make('lot_id', 'Lot')->build(),
            ColumnBuilder::make('project_id', 'Project')->build(),
            ColumnBuilder::make('agent_contact_id', 'Agent')->build(),
            ColumnBuilder::make('primary_contact_id', 'Primary Contact')->build(),
            ColumnBuilder::make('created_at', 'Created')->sortable()->build(),
        ];
    }

    #[Override]
    public static function tableAllowedFilters(): array
    {
        return [
            AllowedFilter::exact('stage'),
            AllowedFilter::exact('deposit_status'),
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
                columns: ['id', 'stage', 'deposit_status', 'purchase_price', 'lot_id', 'project_id', 'primary_contact_id', 'created_at'],
            ),
            new QuickView(
                id: 'active',
                label: 'Active',
                params: ['filter[stage]' => 'active'],
                icon: 'check-circle',
                columns: ['id', 'stage', 'deposit_status', 'purchase_price', 'lot_id', 'project_id', 'primary_contact_id', 'created_at'],
            ),
            new QuickView(
                id: 'settling_soon',
                label: 'Settling Soon',
                params: ['filter[stage]' => 'settling_soon'],
                icon: 'clock',
                columns: ['id', 'stage', 'deposit_status', 'purchase_price', 'lot_id', 'project_id', 'primary_contact_id', 'created_at'],
            ),
            new QuickView(
                id: 'settled',
                label: 'Settled',
                params: ['filter[stage]' => 'settled'],
                icon: 'flag',
                columns: ['id', 'stage', 'deposit_status', 'purchase_price', 'lot_id', 'project_id', 'primary_contact_id', 'created_at'],
            ),
        ];
    }

    #[Override]
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

    #[Override]
    public static function tableBaseQuery(): Builder
    {
        return PropertyReservation::query();
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

    public static function tableExportName(): string
    {
        return 'property-reservations';
    }

    public static function tableAiSystemContext(): string
    {
        return 'You are analyzing a property reservation pipeline for a real estate sales agency. Reservations track buyers from initial enquiry to settlement. Stages: enquiry/qualified/reservation/contract/unconditional/settled. Key fields: deposit_status, settlement_date, assigned_agent. Help identify at-risk deals, upcoming settlements, and unpaid deposits.';
    }
}
