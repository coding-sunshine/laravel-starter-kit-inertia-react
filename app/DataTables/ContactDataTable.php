<?php

declare(strict_types=1);

namespace App\DataTables;

use App\Models\Contact;
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
final class ContactDataTable extends AbstractDataTable
{
    use HasAi;
    use HasExport;

    #[Override]
    protected static ?int $defaultPerPage = 25;

    #[Override]
    protected static ?int $maxPerPage = 100;

    public function __construct(
        public int $id,
        public string $first_name,
        public ?string $last_name,
        public string $type,
        public ?string $stage,
        public string $contact_origin,
        public ?string $company_name,
        public ?int $lead_score,
        public ?string $last_contacted_at,
        public ?string $next_followup_at,
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
            contact_origin: $model->contact_origin,
            company_name: $model->company_name,
            lead_score: $model->lead_score,
            last_contacted_at: $model->last_contacted_at?->format('Y-m-d H:i'),
            next_followup_at: $model->next_followup_at?->format('Y-m-d H:i'),
            created_at: $model->created_at?->format('Y-m-d H:i'),
        );
    }

    #[Override]
    public static function tableColumns(): array
    {
        return [
            ColumnBuilder::make('id')->label('ID')->sortable(),
            ColumnBuilder::make('first_name')->label('First Name')->sortable()->searchable(),
            ColumnBuilder::make('last_name')->label('Last Name')->sortable()->searchable(),
            ColumnBuilder::make('type')->label('Type')->sortable(),
            ColumnBuilder::make('stage')->label('Stage')->sortable(),
            ColumnBuilder::make('contact_origin')->label('Origin')->sortable(),
            ColumnBuilder::make('company_name')->label('Company')->searchable(),
            ColumnBuilder::make('lead_score')->label('Score')->sortable(),
            ColumnBuilder::make('last_contacted_at')->label('Last Contacted')->sortable(),
            ColumnBuilder::make('next_followup_at')->label('Next Follow-up')->sortable(),
            ColumnBuilder::make('created_at')->label('Created')->sortable(),
        ];
    }

    #[Override]
    public static function tableAllowedFilters(): array
    {
        return [
            AllowedFilter::partial('first_name'),
            AllowedFilter::partial('last_name'),
            AllowedFilter::partial('company_name'),
            AllowedFilter::exact('type'),
            AllowedFilter::exact('stage'),
            AllowedFilter::exact('contact_origin'),
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
                columns: ['id', 'first_name', 'last_name', 'type', 'stage', 'company_name', 'last_contacted_at', 'created_at'],
            ),
            new QuickView(
                id: 'leads',
                label: 'Leads',
                params: ['filter[type]' => 'lead'],
                icon: 'user',
                columns: ['id', 'first_name', 'last_name', 'stage', 'company_name', 'lead_score', 'last_contacted_at'],
            ),
            new QuickView(
                id: 'clients',
                label: 'Clients',
                params: ['filter[type]' => 'client'],
                icon: 'check-circle',
                columns: ['id', 'first_name', 'last_name', 'company_name', 'last_contacted_at'],
            ),
            new QuickView(
                id: 'hot',
                label: 'Hot',
                params: ['filter[stage]' => 'hot'],
                icon: 'flame',
                columns: ['id', 'first_name', 'last_name', 'type', 'company_name', 'lead_score', 'next_followup_at'],
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
        return Contact::query();
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
        return 'contacts';
    }

    #[Override]
    public static function tableAiSystemContext(): string
    {
        return 'You are analyzing a CRM contacts table for a real estate agency. Contacts represent buyers, investors, and vendors. Key fields: stage (new/qualified/hot/warm/cold/dead), lead_score (0–100, AI-generated), last_contacted_at (days since last interaction). Help agents identify who to follow up with and surface pipeline insights.';
    }
}
