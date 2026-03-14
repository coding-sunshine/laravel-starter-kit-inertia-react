<?php

declare(strict_types=1);

namespace App\DataTables;

use App\Models\Commission;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Machour\DataTable\AbstractDataTable;
use Machour\DataTable\Columns\ColumnBuilder;
use Machour\DataTable\Concerns\HasAi;
use Machour\DataTable\Concerns\HasExport;
use Override;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
final class CommissionReportDataTable extends AbstractDataTable
{
    use HasAi;
    use HasExport;

    #[Override]
    protected static ?int $defaultPerPage = 25;

    public function __construct(
        public int $id,
        public ?string $commission_type,
        public ?float $amount,
        public ?float $rate_percentage,
        public ?string $agent_name,
        public ?string $project_name,
        public ?bool $override_amount,
        public ?string $created_at,
    ) {}

    public static function fromModel(Commission $model): self
    {
        return new self(
            id: $model->id,
            commission_type: $model->commission_type,
            amount: $model->amount,
            rate_percentage: $model->rate_percentage,
            agent_name: $model->agentUser?->name,
            project_name: $model->sale?->lot?->project?->name,
            override_amount: $model->override_amount,
            created_at: $model->created_at?->format('Y-m-d'),
        );
    }

    #[Override]
    public static function tableColumns(): array
    {
        return [
            ColumnBuilder::make('id')->label('ID')->sortable(),
            ColumnBuilder::make('commission_type')->label('Type')->sortable(),
            ColumnBuilder::make('amount')->label('Amount')->sortable(),
            ColumnBuilder::make('rate_percentage')->label('Rate %')->sortable(),
            ColumnBuilder::make('agent_name')->label('Agent'),
            ColumnBuilder::make('project_name')->label('Project'),
            ColumnBuilder::make('override_amount')->label('Override'),
            ColumnBuilder::make('created_at')->label('Created')->sortable(),
        ];
    }

    #[Override]
    public static function tableAllowedFilters(): array
    {
        return [
            AllowedFilter::exact('commission_type'),
        ];
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
        return Commission::query()->with(['agentUser', 'sale.lot.project']);
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
        return 'commissions-report';
    }

    #[Override]
    public static function tableAiSystemContext(): string
    {
        return 'You are analyzing a commission report for a real estate agency. Key fields: commission_type, amount, rate_percentage, agent. Filter by commission_type to find totals by type and agent performance.';
    }
}
