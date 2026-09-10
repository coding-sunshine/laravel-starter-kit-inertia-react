<?php

declare(strict_types=1);

namespace App\DataTables;

use App\Models\PenaltyType;
use App\Models\RrPenaltySnapshot;
use App\Models\Siding;
use App\Support\PenaltyDateFilter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Machour\DataTable\AbstractDataTable;
use Machour\DataTable\Columns\Column;
use Machour\DataTable\Filters\OperatorFilter;
use Machour\DataTable\QuickView;
use Spatie\QueryBuilder\AllowedFilter;

final class PenaltyDataTable extends AbstractDataTable
{
    public function __construct(
        public int $id,
        public ?int $rake_id,
        public string $penalty_code,
        public string $penalty_type_name,
        public string $penalty_amount,
        public string $penalty_date,
        public ?string $rake_number,
        public ?string $siding_name,
    ) {}

    public static function fromModel(RrPenaltySnapshot $model): self
    {
        return new self(
            id: $model->id,
            rake_id: $model->rake_id,
            penalty_code: $model->penalty_code,
            penalty_type_name: (string) ($model->getAttribute('penalty_type_name') ?? $model->penalty_code),
            penalty_amount: (string) $model->amount,
            penalty_date: $model->getAttribute('penalty_date') !== null
                ? Carbon::parse($model->getAttribute('penalty_date'))->format('Y-m-d')
                : '',
            rake_number: $model->getAttribute('rake_number'),
            siding_name: $model->getAttribute('siding_name'),
        );
    }

    public static function tableColumns(): array
    {
        $penaltyTypeOptions = PenaltyType::query()
            ->orderBy('name')
            ->get(['code', 'name'])
            ->map(fn (PenaltyType $type): array => ['label' => $type->name, 'value' => $type->code])
            ->values()
            ->all();

        return [
            new Column(id: 'rake_number', label: 'Rake', type: 'text', sortable: false, filterable: false),
            new Column(id: 'siding_name', label: 'Siding', type: 'text', sortable: false, filterable: false),
            new Column(id: 'penalty_code', label: 'Type', type: 'option', sortable: true, filterable: true, options: $penaltyTypeOptions),
            new Column(id: 'penalty_type_name', label: 'Type name', type: 'text', sortable: false, filterable: false),
            new Column(id: 'penalty_amount', label: 'Amount', type: 'number', sortable: true, filterable: false),
            new Column(id: 'penalty_date', label: 'Date', type: 'date', sortable: true, filterable: true),
        ];
    }

    public static function tableQuickViews(): array
    {
        return [
            new QuickView(id: 'all', label: 'All', params: []),
            new QuickView(
                id: 'this_month',
                label: 'This month',
                params: [
                    'filter[penalty_date]' => 'gte:'.now()->startOfMonth()->toDateString(),
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

        return RrPenaltySnapshot::query()
            ->select('rr_penalty_snapshots.*')
            ->selectRaw('rakes.rake_number as rake_number, sidings.name as siding_name')
            ->selectRaw('COALESCE(penalty_types.name, rr_penalty_snapshots.penalty_code) as penalty_type_name')
            ->selectRaw(PenaltyDateFilter::DATE_EXPR.' as penalty_date')
            ->leftJoin('rr_documents', 'rr_penalty_snapshots.rr_document_id', '=', 'rr_documents.id')
            ->leftJoin('rakes', 'rr_penalty_snapshots.rake_id', '=', 'rakes.id')
            ->leftJoin('sidings', 'rakes.siding_id', '=', 'sidings.id')
            ->leftJoin('penalty_types', 'rr_penalty_snapshots.penalty_code', '=', 'penalty_types.code')
            ->whereIn('rakes.siding_id', $sidingIds);
    }

    public static function tableDefaultSort(): string
    {
        return '-penalty_date';
    }

    public static function tableAllowedFilters(): array
    {
        return [
            AllowedFilter::custom('penalty_code', new OperatorFilter('option')),
            AllowedFilter::callback('penalty_date', fn (Builder $query, mixed $value) => PenaltyDateFilter::apply($query, $value)),
            AllowedFilter::custom('rake_id', new OperatorFilter('number', 'rr_penalty_snapshots.rake_id')),
            AllowedFilter::callback('siding_id', fn (Builder $query, mixed $value) => $query->where('rakes.siding_id', $value)),
        ];
    }

    public static function tableAllowedSorts(): array
    {
        return ['penalty_code', 'penalty_amount', 'penalty_date'];
    }

    public static function tableFooter(\Illuminate\Support\Collection $items): array
    {
        $total = $items->sum(fn ($row) => (float) $row->penalty_amount);

        return ['penalty_amount' => round($total, 2)];
    }
}
