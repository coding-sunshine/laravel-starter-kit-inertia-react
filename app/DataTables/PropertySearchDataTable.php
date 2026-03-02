<?php

declare(strict_types=1);

namespace App\DataTables;

use App\Models\PropertySearch;
use Illuminate\Database\Eloquent\Builder;
use Machour\DataTable\AbstractDataTable;
use Machour\DataTable\Columns\Column;
use Machour\DataTable\QuickView;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
final class PropertySearchDataTable extends AbstractDataTable
{
    public function __construct(
        public int $id,
        public ?string $client_contact_name,
        public ?string $agent_contact_name,
        public ?string $preferred_location,
        public ?int $no_of_bedrooms,
        public ?int $no_of_bathrooms,
        public bool $preapproval,
        public ?string $created_at,
    ) {}

    public static function fromModel(PropertySearch $model): self
    {
        return new self(
            id: $model->id,
            client_contact_name: $model->clientContact?->full_name,
            agent_contact_name: $model->agentContact?->full_name,
            preferred_location: $model->preferred_location,
            no_of_bedrooms: $model->no_of_bedrooms,
            no_of_bathrooms: $model->no_of_bathrooms,
            preapproval: (bool) $model->preapproval,
            created_at: $model->created_at?->format('Y-m-d H:i'),
        );
    }

    public static function tableColumns(): array
    {
        return [
            new Column(id: 'id', label: 'ID', type: 'number', sortable: true),
            new Column(id: 'client_contact_name', label: 'Client', type: 'string', sortable: false),
            new Column(id: 'agent_contact_name', label: 'Agent', type: 'string', sortable: false),
            new Column(id: 'preferred_location', label: 'Preferred location', type: 'string', sortable: false),
            new Column(id: 'no_of_bedrooms', label: 'Bedrooms', type: 'number', sortable: true),
            new Column(id: 'no_of_bathrooms', label: 'Bathrooms', type: 'number', sortable: true),
            new Column(id: 'preapproval', label: 'Preapproval', type: 'boolean', sortable: true),
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
        return PropertySearch::query()
            ->with(['clientContact', 'agentContact']);
    }

    public static function tableDefaultSort(): string
    {
        return '-id';
    }
}
