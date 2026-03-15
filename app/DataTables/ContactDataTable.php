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
use Machour\DataTable\Concerns\HasInlineEdit;
use Machour\DataTable\QuickView;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
final class ContactDataTable extends AbstractDataTable
{
    use HasAi;
    use HasExport;
    use HasInlineEdit;

    protected static ?int $defaultPerPage = 25;

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

    public static function tableColumns(): array
    {
        return [
            ColumnBuilder::make('id', 'ID')->sortable()->build(),
            ColumnBuilder::make('first_name', 'First Name')->sortable()->build(),
            ColumnBuilder::make('last_name', 'Last Name')->sortable()->build(),
            ColumnBuilder::make('type', 'Type')->sortable()->build(),
            ColumnBuilder::make('stage', 'Stage')->sortable()->editable()->build(),
            ColumnBuilder::make('contact_origin', 'Origin')->sortable()->build(),
            ColumnBuilder::make('company_name', 'Company')->build(),
            ColumnBuilder::make('lead_score', 'Score')->sortable()->editable()->build(),
            ColumnBuilder::make('last_contacted_at', 'Last Contacted')->sortable()->build(),
            ColumnBuilder::make('next_followup_at', 'Next Follow-up')->sortable()->editable()->build(),
            ColumnBuilder::make('created_at', 'Created')->sortable()->build(),
        ];
    }

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

    public static function tableSoftDeletesEnabled(): bool
    {
        return true;
    }

    public static function inertiaProps(Request $request): array
    {
        return [
            'tableData' => self::makeTable($request)->toArray(),
            'searchableColumns' => self::tableSearchableColumns(),
        ];
    }

    public static function tableInlineEditModel(): string
    {
        return Contact::class;
    }

    public static function tableAnalytics(): array
    {
        return [
            [
                'label' => 'Total Contacts',
                'value' => Contact::query()->count(),
                'icon' => 'users',
                'color' => 'blue',
            ],
            [
                'label' => 'New This Week',
                'value' => Contact::query()->where('created_at', '>=', now()->subWeek())->count(),
                'icon' => 'user-plus',
                'color' => 'green',
            ],
            [
                'label' => 'Hot Leads',
                'value' => Contact::query()->where('stage', 'hot')->count(),
                'icon' => 'flame',
                'color' => 'red',
            ],
            [
                'label' => 'Avg Lead Score',
                'value' => (int) Contact::query()->whereNotNull('lead_score')->avg('lead_score'),
                'icon' => 'target',
                'color' => 'amber',
            ],
        ];
    }

    public static function tableBaseQuery(): Builder
    {
        return Contact::query();
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
        return 'contacts';
    }

    public static function tableAiSystemContext(): string
    {
        return 'You are analyzing a CRM contacts table for a real estate agency. Contacts represent buyers, investors, and vendors. Key fields: stage (new/qualified/hot/warm/cold/dead), lead_score (0–100, AI-generated), last_contacted_at (days since last interaction). Help agents identify who to follow up with and surface pipeline insights.';
    }
}
