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
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
final class ReservationReportDataTable extends AbstractDataTable
{
    use HasAi;
    use HasExport;

    protected static ?int $defaultPerPage = 25;

    public function __construct(
        public int $id,
        public ?string $contact_name,
        public ?string $project_name,
        public ?string $lot_number,
        public ?string $stage,
        public ?string $agent_name,
        public ?float $purchase_price,
        public ?string $created_at,
    ) {}

    public static function fromModel(PropertyReservation $model): self
    {
        return new self(
            id: $model->id,
            contact_name: $model->primaryContact ? ($model->primaryContact->first_name.' '.$model->primaryContact->last_name) : null,
            project_name: $model->lot?->project?->name,
            lot_number: $model->lot?->title ?? $model->lot?->slug,
            stage: $model->stage,
            agent_name: $model->agentContact ? ($model->agentContact->first_name.' '.$model->agentContact->last_name) : null,
            purchase_price: is_numeric($model->purchase_price) ? (float) $model->purchase_price : null,
            created_at: $model->created_at?->format('Y-m-d'),
        );
    }

    public static function tableColumns(): array
    {
        return [
            ColumnBuilder::make('id', 'ID')->sortable()->build(),
            ColumnBuilder::make('contact_name', 'Contact')->build(),
            ColumnBuilder::make('project_name', 'Project')->build(),
            ColumnBuilder::make('lot_number', 'Lot')->build(),
            ColumnBuilder::make('stage', 'Stage')->sortable()->build(),
            ColumnBuilder::make('agent_name', 'Agent')->build(),
            ColumnBuilder::make('purchase_price', 'Price')->currency('AUD')->sortable()->build(),
            ColumnBuilder::make('created_at', 'Created')->sortable()->build(),
        ];
    }

    public static function tableAllowedFilters(): array
    {
        return [
            AllowedFilter::exact('stage'),
        ];
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
        return PropertyReservation::query()->with(['primaryContact', 'lot.project', 'agentContact']);
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
        return 'reservations-report';
    }

    public static function tableAiSystemContext(): string
    {
        return 'You are analyzing a property reservation report for a real estate CRM. Key fields: stage (enquiry/qualified/reservation/contract/unconditional/settled), purchase_price, agent_name. Help identify pipeline bottlenecks, settlement rates, and agent performance.';
    }
}
