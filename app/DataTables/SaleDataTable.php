<?php

declare(strict_types=1);

namespace App\DataTables;

use App\Models\Sale;
use Illuminate\Database\Eloquent\Builder;
use Machour\DataTable\AbstractDataTable;
use Machour\DataTable\Columns\Column;
use Machour\DataTable\QuickView;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
final class SaleDataTable extends AbstractDataTable
{
    public function __construct(
        public int $id,
        public ?string $client_contact_name,
        public ?string $project_title,
        public ?int $lot_id,
        public ?string $comms_in_total,
        public ?string $comms_out_total,
        public ?string $finance_due_date,
        public ?string $created_at,
    ) {}

    public static function fromModel(Sale $model): self
    {
        return new self(
            id: $model->id,
            client_contact_name: $model->clientContact?->full_name,
            project_title: $model->project?->title,
            lot_id: $model->lot_id,
            comms_in_total: $model->comms_in_total !== null ? (string) $model->comms_in_total : null,
            comms_out_total: $model->comms_out_total !== null ? (string) $model->comms_out_total : null,
            finance_due_date: $model->finance_due_date?->format('Y-m-d'),
            created_at: $model->created_at?->format('Y-m-d H:i'),
        );
    }

    public static function tableColumns(): array
    {
        return [
            new Column(id: 'id', label: 'ID', type: 'number', sortable: true),
            new Column(id: 'client_contact_name', label: 'Client', type: 'string', sortable: false),
            new Column(id: 'project_title', label: 'Project', type: 'string', sortable: false),
            new Column(id: 'lot_id', label: 'Lot', type: 'number', sortable: true),
            new Column(id: 'comms_in_total', label: 'Comms in', type: 'string', sortable: true),
            new Column(id: 'comms_out_total', label: 'Comms out', type: 'string', sortable: true),
            new Column(id: 'finance_due_date', label: 'Finance due', type: 'date', sortable: true),
            new Column(id: 'created_at', label: 'Created at', type: 'date', sortable: true),
        ];
    }

    public static function tableQuickViews(): array
    {
        return [
            new QuickView(id: 'all', label: 'All', params: []),
        ];
    }

    public static function tableBaseQuery(): Builder
    {
        return Sale::query()
            ->with(['clientContact', 'project', 'lot']);
    }

    public static function tableDefaultSort(): string
    {
        return '-id';
    }
}
