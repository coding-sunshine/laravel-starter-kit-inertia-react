<?php

declare(strict_types=1);

namespace App\DataTables;

use App\Models\PropertyReservation;
use Illuminate\Database\Eloquent\Builder;
use Machour\DataTable\AbstractDataTable;
use Machour\DataTable\Columns\Column;
use Machour\DataTable\QuickView;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
final class PropertyReservationDataTable extends AbstractDataTable
{
    public function __construct(
        public int $id,
        public ?string $primary_contact_name,
        public ?string $agent_contact_name,
        public ?string $project_title,
        public ?int $lot_id,
        public ?string $purchase_price,
        public ?string $agree_date,
        public ?string $created_at,
    ) {}

    public static function fromModel(PropertyReservation $model): self
    {
        return new self(
            id: $model->id,
            primary_contact_name: $model->primaryContact?->full_name,
            agent_contact_name: $model->agentContact?->full_name,
            project_title: $model->project?->title,
            lot_id: $model->lot_id,
            purchase_price: $model->purchase_price !== null ? (string) $model->purchase_price : null,
            agree_date: $model->agree_date?->format('Y-m-d'),
            created_at: $model->created_at?->format('Y-m-d H:i'),
        );
    }

    public static function tableColumns(): array
    {
        return [
            new Column(id: 'id', label: 'ID', type: 'number', sortable: true),
            new Column(id: 'primary_contact_name', label: 'Primary contact', type: 'string', sortable: false),
            new Column(id: 'agent_contact_name', label: 'Agent', type: 'string', sortable: false),
            new Column(id: 'project_title', label: 'Project', type: 'string', sortable: false),
            new Column(id: 'lot_id', label: 'Lot', type: 'number', sortable: true),
            new Column(id: 'purchase_price', label: 'Purchase price', type: 'string', sortable: true),
            new Column(id: 'agree_date', label: 'Agree date', type: 'date', sortable: true),
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
        return PropertyReservation::query()
            ->with(['primaryContact', 'agentContact', 'project', 'lot']);
    }

    public static function tableDefaultSort(): string
    {
        return '-id';
    }
}
