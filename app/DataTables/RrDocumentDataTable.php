<?php

declare(strict_types=1);

namespace App\DataTables;

use App\Models\RrDocument;
use App\Models\Siding;
use Illuminate\Database\Eloquent\Builder;
use Machour\DataTable\AbstractDataTable;
use Machour\DataTable\Columns\Column;
use Machour\DataTable\Filters\OperatorFilter;
use Machour\DataTable\QuickView;
use Spatie\QueryBuilder\AllowedFilter;

final class RrDocumentDataTable extends AbstractDataTable
{
    public function __construct(
        public int $id,
        public ?int $rake_id,
        public string $rr_number,
        public string $rr_received_date,
        public ?string $rr_weight_mt,
        public string $document_status,
        public ?string $rake_number,
        public ?string $siding_name,
    ) {}

    public static function fromModel(RrDocument $model): self
    {
        return new self(
            id: $model->id,
            rake_id: $model->rake_id,
            rr_number: $model->rr_number,
            rr_received_date: $model->rr_received_date?->format('Y-m-d') ?? '',
            rr_weight_mt: $model->rr_weight_mt !== null ? (string) $model->rr_weight_mt : null,
            document_status: $model->document_status,
            rake_number: $model->rake?->rake_number,
            siding_name: $model->rake?->siding?->name,
        );
    }

    public static function tableColumns(): array
    {
        return [
            new Column(id: 'rr_number', label: 'RR number', type: 'text', sortable: true, filterable: true),
            new Column(id: 'rake_number', label: 'Rake', type: 'text', sortable: false, filterable: false),
            new Column(id: 'siding_name', label: 'Siding', type: 'text', sortable: false, filterable: false),
            new Column(id: 'rr_received_date', label: 'Received date', type: 'date', sortable: true, filterable: true),
            new Column(id: 'rr_weight_mt', label: 'Weight (MT)', type: 'number', sortable: true, filterable: false),
            new Column(id: 'document_status', label: 'Status', type: 'option', sortable: true, filterable: true, options: [
                ['label' => 'Received', 'value' => 'received'],
                ['label' => 'Verified', 'value' => 'verified'],
                ['label' => 'Discrepancy', 'value' => 'discrepancy'],
            ]),
        ];
    }

    public static function tableQuickViews(): array
    {
        return [
            new QuickView(id: 'all', label: 'All', params: []),
            new QuickView(
                id: 'recent',
                label: 'Last 30 days',
                params: ['filter[rr_received_date]' => 'after:'.now()->subDays(30)->toDateString()],
                icon: 'calendar',
            ),
        ];
    }

    public static function tableBaseQuery(): Builder
    {
        $user = request()->user();
        $sidingIds = $user && $user->isSuperAdmin()
            ? Siding::query()->pluck('id')->all()
            : ($user ? $user->accessibleSidings()->get()->pluck('id')->all() : []);

        return RrDocument::query()
            ->with('rake.siding:id,name,code')
            ->when(
                $sidingIds !== [],
                fn (Builder $query): Builder => $query->where(function (Builder $inner) use ($sidingIds): void {
                    $inner
                        ->whereHas('rake', fn (Builder $q): Builder => $q->whereIn('siding_id', $sidingIds))
                        ->orWhereNull('rake_id');
                }),
            );
    }

    public static function tableDefaultSort(): string
    {
        return '-rr_received_date';
    }

    public static function tableAllowedFilters(): array
    {
        return [
            AllowedFilter::custom('rr_number', new OperatorFilter('text')),
            AllowedFilter::custom('rake_id', new OperatorFilter('number')),
            AllowedFilter::custom('rr_received_date', new OperatorFilter('date')),
            AllowedFilter::custom('document_status', new OperatorFilter('option')),
            AllowedFilter::callback('siding_id', fn ($query, $value) => $query->whereHas('rake', fn ($q) => $q->where('siding_id', $value))),
        ];
    }

    public static function tableAllowedSorts(): array
    {
        return ['rr_number', 'rr_received_date', 'rr_weight_mt', 'document_status'];
    }
}
