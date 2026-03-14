<?php

declare(strict_types=1);

namespace App\DataTables;

use App\Models\Commission;
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
final class CommissionDataTable extends AbstractDataTable
{
    use HasExport;

    #[Override]
    protected static ?int $defaultPerPage = 25;

    #[Override]
    protected static ?int $maxPerPage = 100;

    public function __construct(
        public int $id,
        public int $sale_id,
        public string $commission_type,
        public ?int $agent_user_id,
        public ?float $rate_percentage,
        public float $amount,
        public bool $override_amount,
        public ?string $created_at,
    ) {}

    public static function fromModel(Commission $model): self
    {
        return new self(
            id: $model->id,
            sale_id: $model->sale_id,
            commission_type: $model->commission_type,
            agent_user_id: $model->agent_user_id,
            rate_percentage: $model->rate_percentage,
            amount: $model->amount,
            override_amount: $model->override_amount,
            created_at: $model->created_at?->format('Y-m-d H:i'),
        );
    }

    #[Override]
    public static function tableColumns(): array
    {
        return [
            ColumnBuilder::make('id')->label('ID')->sortable(),
            ColumnBuilder::make('sale_id')->label('Sale')->sortable(),
            ColumnBuilder::make('commission_type')->label('Type')->sortable(),
            ColumnBuilder::make('agent_user_id')->label('Agent'),
            ColumnBuilder::make('rate_percentage')->label('Rate %')->sortable(),
            ColumnBuilder::make('amount')->label('Amount')->sortable(),
            ColumnBuilder::make('override_amount')->label('Override'),
            ColumnBuilder::make('created_at')->label('Created')->sortable(),
        ];
    }

    #[Override]
    public static function tableAllowedFilters(): array
    {
        return [
            AllowedFilter::exact('commission_type'),
            AllowedFilter::exact('sale_id'),
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
                columns: ['id', 'sale_id', 'commission_type', 'agent_user_id', 'rate_percentage', 'amount', 'override_amount', 'created_at'],
            ),
            new QuickView(
                id: 'piab',
                label: 'PIAB',
                params: ['filter[commission_type]' => 'piab'],
                icon: 'dollar-sign',
                columns: ['id', 'sale_id', 'agent_user_id', 'rate_percentage', 'amount', 'override_amount', 'created_at'],
            ),
            new QuickView(
                id: 'subscriber',
                label: 'Subscriber',
                params: ['filter[commission_type]' => 'subscriber'],
                icon: 'user-check',
                columns: ['id', 'sale_id', 'agent_user_id', 'rate_percentage', 'amount', 'override_amount', 'created_at'],
            ),
            new QuickView(
                id: 'sales_agent',
                label: 'Sales Agent',
                params: ['filter[commission_type]' => 'sales_agent'],
                icon: 'briefcase',
                columns: ['id', 'sale_id', 'agent_user_id', 'rate_percentage', 'amount', 'override_amount', 'created_at'],
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
        return Commission::query();
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
        return 'commissions';
    }
}
