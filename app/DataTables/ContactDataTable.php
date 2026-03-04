<?php

declare(strict_types=1);

namespace App\DataTables;

use App\Models\Contact;
use Illuminate\Database\Eloquent\Builder;
use Machour\DataTable\AbstractDataTable;
use Machour\DataTable\Columns\Column;
use Machour\DataTable\QuickView;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
final class ContactDataTable extends AbstractDataTable
{
    public function __construct(
        public int $id,
        public ?string $first_name,
        public ?string $last_name,
        public ?string $type,
        public ?string $stage,
        public ?string $company_name,
        public ?string $source_name,
        public ?string $created_at,
    ) {}

    public static function fromModel(Contact $model): self
    {
        return new self(
            id: $model->id,
            first_name: $model->first_name,
            last_name: $model->last_name,
            type: $model->type,
            stage: $model->stage,
            company_name: $model->company?->name ?? $model->company_name,
            source_name: $model->source?->label,
            created_at: $model->created_at?->format('Y-m-d H:i'),
        );
    }

    public static function tableColumns(): array
    {
        return [
            new Column(id: 'id', label: 'ID', type: 'number', sortable: true),
            new Column(id: 'first_name', label: 'First name', type: 'string', sortable: true),
            new Column(id: 'last_name', label: 'Last name', type: 'string', sortable: true),
            new Column(id: 'type', label: 'Type', type: 'string', sortable: true),
            new Column(id: 'stage', label: 'Stage', type: 'string', sortable: true),
            new Column(id: 'company_name', label: 'Company', type: 'string', sortable: false),
            new Column(id: 'source_name', label: 'Source', type: 'string', sortable: false),
            new Column(id: 'created_at', label: 'Created at', type: 'date', sortable: true),
        ];
    }

    public static function tableQuickViews(): array
    {
        return [
            new QuickView(id: 'all', label: 'All', params: []),
            new QuickView(id: 'leads', label: 'Leads', params: ['filter[type]' => 'lead']),
            new QuickView(id: 'clients', label: 'Clients', params: ['filter[type]' => 'client']),
            new QuickView(id: 'agents', label: 'Agents', params: ['filter[type]' => 'agent']),
        ];
    }

    public static function tableBaseQuery(): Builder
    {
        return Contact::query()
            ->with(['source', 'company'])
            ->leftJoin('sources', 'contacts.source_id', '=', 'sources.id')
            ->leftJoin('companies', 'contacts.company_id', '=', 'companies.id')
            ->select('contacts.*', 'companies.name as company_name_search', 'sources.label as source_label_search');
    }

    public static function tableDefaultSort(): string
    {
        return '-id';
    }

    public static function tableSearchableColumns(): array
    {
        return [
            'contacts.first_name',
            'contacts.last_name',
            'companies.name',
            'contacts.company_name',
            'contacts.type',
            'contacts.stage',
        ];
    }
}
