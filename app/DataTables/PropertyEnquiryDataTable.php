<?php

declare(strict_types=1);

namespace App\DataTables;

use App\Models\PropertyEnquiry;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Machour\DataTable\AbstractDataTable;
use Machour\DataTable\Columns\ColumnBuilder;
use Machour\DataTable\QuickView;
use Override;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
final class PropertyEnquiryDataTable extends AbstractDataTable
{
    #[Override]
    protected static ?int $defaultPerPage = 25;

    #[Override]
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

    #[Override]
    public static function tableColumns(): array
    {
        return [
            ColumnBuilder::make('id')->label('ID')->sortable(),
            ColumnBuilder::make('status')->label('Status')->sortable(),
            ColumnBuilder::make('client_contact_id')->label('Client'),
            ColumnBuilder::make('agent_contact_id')->label('Agent'),
            ColumnBuilder::make('lot_id')->label('Lot'),
            ColumnBuilder::make('project_id')->label('Project'),
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
        return PropertyEnquiry::query();
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
