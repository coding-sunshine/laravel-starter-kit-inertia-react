<?php

declare(strict_types=1);

namespace App\DataTables;

use App\Models\Penalty;
use App\Models\Siding;
use Illuminate\Database\Eloquent\Builder;
use Machour\DataTable\AbstractDataTable;
use Machour\DataTable\Columns\Column;
use Machour\DataTable\Filters\OperatorFilter;
use Machour\DataTable\QuickView;
use Spatie\QueryBuilder\AllowedFilter;

final class PenaltyDataTable extends AbstractDataTable
{
    public function __construct(
        public int $id,
        public int $rake_id,
        public string $penalty_type,
        public string $penalty_amount,
        public string $penalty_status,
        public string $penalty_date,
        public ?string $responsible_party,
        public ?string $root_cause,
        public ?array $calculation_breakdown,
        public ?string $rake_number,
        public ?string $siding_name,
    ) {}

    public static function fromModel(Penalty $model): self
    {
        return new self(
            id: $model->id,
            rake_id: $model->rake_id,
            penalty_type: $model->penalty_type,
            penalty_amount: (string) $model->penalty_amount,
            penalty_status: $model->penalty_status,
            penalty_date: $model->penalty_date?->format('Y-m-d') ?? '',
            responsible_party: $model->responsible_party,
            root_cause: $model->root_cause,
            calculation_breakdown: $model->calculation_breakdown,
            rake_number: $model->rake?->rake_number,
            siding_name: $model->rake?->siding?->name,
        );
    }

    public static function tableColumns(): array
    {
        return [
            new Column(id: 'rake_number', label: 'Rake', type: 'text', sortable: false, filterable: false),
            new Column(id: 'siding_name', label: 'Siding', type: 'text', sortable: false, filterable: false),
            new Column(id: 'penalty_type', label: 'Type', type: 'option', sortable: true, filterable: true, options: [
                ['label' => 'DEM', 'value' => 'DEM'],
                ['label' => 'POL1', 'value' => 'POL1'],
                ['label' => 'POLA', 'value' => 'POLA'],
                ['label' => 'PLO', 'value' => 'PLO'],
                ['label' => 'ULC', 'value' => 'ULC'],
                ['label' => 'SPL', 'value' => 'SPL'],
                ['label' => 'WMC', 'value' => 'WMC'],
                ['label' => 'MCF', 'value' => 'MCF'],
            ]),
            new Column(id: 'penalty_amount', label: 'Amount', type: 'number', sortable: true, filterable: false),
            new Column(id: 'penalty_status', label: 'Status', type: 'option', sortable: true, filterable: true, options: [
                ['label' => 'Pending', 'value' => 'pending'],
                ['label' => 'Incurred', 'value' => 'incurred'],
                ['label' => 'Disputed', 'value' => 'disputed'],
                ['label' => 'Waived', 'value' => 'waived'],
            ]),
            new Column(id: 'penalty_date', label: 'Date', type: 'date', sortable: true, filterable: true),
            new Column(id: 'responsible_party', label: 'Responsible', type: 'option', sortable: true, filterable: true, options: [
                ['label' => 'Railway', 'value' => 'railway'],
                ['label' => 'Siding', 'value' => 'siding'],
                ['label' => 'Transporter', 'value' => 'transporter'],
                ['label' => 'Plant', 'value' => 'plant'],
                ['label' => 'Other', 'value' => 'other'],
            ]),
            new Column(id: 'root_cause', label: 'Root Cause', type: 'text', sortable: false, filterable: false),
        ];
    }

    public static function tableQuickViews(): array
    {
        return [
            new QuickView(id: 'all', label: 'All', params: []),
            new QuickView(
                id: 'pending',
                label: 'Pending',
                params: ['filter[penalty_status]' => 'eq:pending'],
                icon: 'clock',
            ),
            new QuickView(
                id: 'this_month',
                label: 'This month',
                params: [
                    'filter[penalty_date]' => 'after:'.now()->startOfMonth()->toDateString(),
                ],
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

        return Penalty::query()
            ->with('rake.siding:id,name,code')
            ->whereHas('rake', fn ($q) => $q->whereIn('siding_id', $sidingIds));
    }

    public static function tableDefaultSort(): string
    {
        return '-penalty_date';
    }

    public static function tableAllowedFilters(): array
    {
        return [
            AllowedFilter::custom('penalty_type', new OperatorFilter('option')),
            AllowedFilter::custom('penalty_status', new OperatorFilter('option')),
            AllowedFilter::custom('penalty_date', new OperatorFilter('date')),
            AllowedFilter::custom('responsible_party', new OperatorFilter('option')),
            AllowedFilter::custom('rake_id', new OperatorFilter('number')),
            AllowedFilter::callback('siding_id', fn ($query, $value) => $query->whereHas('rake', fn ($q) => $q->where('siding_id', $value))),
        ];
    }

    public static function tableAllowedSorts(): array
    {
        return ['penalty_type', 'penalty_amount', 'penalty_status', 'penalty_date', 'responsible_party'];
    }

    public static function tableFooter(\Illuminate\Support\Collection $items): array
    {
        $total = $items->sum(fn ($row) => (float) $row->penalty_amount);

        return ['penalty_amount' => round($total, 2)];
    }
}
