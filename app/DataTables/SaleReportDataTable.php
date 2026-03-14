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
use Override;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
final class SaleReportDataTable extends AbstractDataTable
{
    use HasAi;
    use HasExport;

    #[Override]
    protected static ?int $defaultPerPage = 25;

    public function __construct(
        public int $id,
        public ?string $contact_name,
        public ?string $project_name,
        public ?string $status,
        public ?string $agent_name,
        public ?float $comms_in_total,
        public ?float $comms_out_total,
        public ?float $commission_total,
        public ?string $settled_at,
        public ?string $created_at,
    ) {}

    public static function fromModel(Sale $model): self
    {
        return new self(
            id: $model->id,
            contact_name: $model->clientContact ? ($model->clientContact->first_name.' '.$model->clientContact->last_name) : null,
            project_name: $model->lot?->project?->name,
            status: $model->status,
            agent_name: $model->salesAgentContact ? ($model->salesAgentContact->first_name.' '.$model->salesAgentContact->last_name) : null,
            comms_in_total: $model->comms_in_total,
            comms_out_total: $model->comms_out_total,
            commission_total: $model->commissions()->sum('amount'),
            settled_at: $model->settled_at?->format('Y-m-d'),
            created_at: $model->created_at?->format('Y-m-d'),
        );
    }

    #[Override]
    public static function tableColumns(): array
    {
        return [
            ColumnBuilder::make('id')->label('ID')->sortable(),
            ColumnBuilder::make('contact_name')->label('Contact')->searchable(),
            ColumnBuilder::make('project_name')->label('Project'),
            ColumnBuilder::make('status')->label('Status')->sortable(),
            ColumnBuilder::make('agent_name')->label('Agent'),
            ColumnBuilder::make('comms_in_total')->label('Comms In')->sortable(),
            ColumnBuilder::make('comms_out_total')->label('Comms Out')->sortable(),
            ColumnBuilder::make('commission_total')->label('Commission Total'),
            ColumnBuilder::make('settled_at')->label('Settled')->sortable(),
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
        return Sale::query()->with(['clientContact', 'lot.project', 'salesAgentContact']);
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
        return 'sales-report';
    }

    #[Override]
    public static function tableAiSystemContext(): string
    {
        return 'You are analyzing a property sales and commission report for management. Key fields: comms_in_total, comms_out_total, commission_total (sum of all agent commissions), agent, settled_at, project. Calculate totals, compare agent performance, and identify commission trends.';
    }
}
