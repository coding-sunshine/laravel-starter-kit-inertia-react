<?php

declare(strict_types=1);

namespace App\DataTables;

use App\Models\Rake;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Machour\DataTable\AbstractDataTable;
use Machour\DataTable\Columns\Column;
use Machour\DataTable\Filters\OperatorFilter;
use Machour\DataTable\QuickView;
use Spatie\QueryBuilder\AllowedFilter;

/**
 * Minimal rake list for /rake-loader (rakes that have at least one weighment).
 * Quick views and column filters match the /rakes DataTable UX.
 */
final class RakeLoaderListDataTable extends AbstractDataTable
{
    public function __construct(
        public int $id,
        public string $rake_number,
        public ?string $rake_serial_number,
        public ?string $siding_code,
        public ?string $loading_date,
        public ?string $siding_label,
        public string $loader_progress_status,
        public int $loader_progress_loaded,
        public int $loader_progress_total,
    ) {}

    public static function fromModel(Rake $model): self
    {
        $siding = $model->relationLoaded('siding') ? $model->siding : $model->siding()->first();
        $label = null;
        if ($siding !== null) {
            $label = $siding->code;
            if ($siding->name !== null && $siding->name !== '') {
                $label .= ' ('.$siding->name.')';
            }
        }

        $progress = $model->rakeLoaderProgressMetrics();

        return new self(
            id: $model->id,
            rake_number: (string) $model->rake_number,
            rake_serial_number: $model->rake_serial_number !== null && $model->rake_serial_number !== ''
                ? (string) $model->rake_serial_number
                : null,
            siding_code: $siding?->code,
            loading_date: $model->loading_date?->toDateString(),
            siding_label: $label,
            loader_progress_status: $progress['status'],
            loader_progress_loaded: $progress['loaded'],
            loader_progress_total: $progress['total'],
        );
    }

    public static function tableColumns(): array
    {
        return [
            new Column(id: 'rake_number', label: 'Rake #', type: 'text', sortable: true, filterable: false),
            new Column(id: 'rake_serial_number', label: 'Rake Number', type: 'text', sortable: false, filterable: false),
            new Column(id: 'siding_label', label: 'Siding', type: 'text', sortable: false, filterable: false),
            new Column(id: 'loading_date', label: 'Loading date', type: 'date', sortable: true, filterable: false),
        ];
    }

    public static function tableQuickViews(): array
    {
        $today = CarbonImmutable::now()->toDateString();
        $yesterday = CarbonImmutable::now()->subDay()->toDateString();
        $weekStart = CarbonImmutable::now()->startOfWeek()->toDateString();
        $monthStart = CarbonImmutable::now()->startOfMonth()->toDateString();

        $now = CarbonImmutable::now();
        if ($now->month >= 4) {
            $fyStart = $now->year.'-04-01';
            $fyEnd = ($now->year + 1).'-03-31';
        } else {
            $fyStart = ($now->year - 1).'-04-01';
            $fyEnd = $now->year.'-03-31';
        }

        return [
            new QuickView(
                id: 'today',
                label: 'Today',
                params: ['filter[loading_date]' => 'between:'.$today.','.$today],
                icon: 'calendar',
            ),
            new QuickView(
                id: 'yesterday',
                label: 'Yesterday',
                params: ['filter[loading_date]' => 'between:'.$yesterday.','.$yesterday],
                icon: 'calendar',
            ),
            new QuickView(
                id: 'this_week',
                label: 'This week',
                params: ['filter[loading_date]' => 'between:'.$weekStart.','.$today],
                icon: 'calendar',
            ),
            new QuickView(
                id: 'this_month',
                label: 'This month',
                params: ['filter[loading_date]' => 'between:'.$monthStart.','.$today],
                icon: 'calendar',
            ),
            new QuickView(
                id: 'financial_year',
                label: 'Financial year',
                params: ['filter[loading_date]' => 'between:'.$fyStart.','.$fyEnd],
                icon: 'calendar',
            ),
        ];
    }

    public static function tableBaseQuery(): Builder
    {
        $query = Rake::query()->with([
            'siding:id,code,name',
            'wagons:id,rake_id,wagon_sequence,wagon_number,is_unfit',
            'wagonLoadings:id,rake_id,wagon_id,loaded_quantity_mt',
        ])->withCount(['rakeWeighments', 'rakeWagonWeighments']);

        $query->where(function (Builder $q): void {
            $q->whereNull('data_source')
                ->orWhereIn('data_source', ['system', 'manual']);
        });

        $query->whereHas('rakeWeighments');

        $user = request()->user();
        if ($user && $user->isSuperAdmin()) {
            $sidingId = request()->query('siding_id');
            if ($sidingId === null || $sidingId === '' || ! is_numeric($sidingId)) {
                return $query->whereRaw('1 = 0');
            }

            $query->where('siding_id', (int) $sidingId);
        } elseif ($user && ! $user->isSuperAdmin()) {
            $sidingIds = $user->sidings()->get()->pluck('id')->all();

            if ($sidingIds === [] && $user->siding_id !== null) {
                $sidingIds = [(int) $user->siding_id];
            }
            $query->whereIn('siding_id', $sidingIds);

            $sidingIdParam = request()->query('siding_id');
            if ($sidingIdParam !== null && $sidingIdParam !== '' && is_numeric($sidingIdParam)) {
                $narrow = (int) $sidingIdParam;
                if ($user->canAccessSiding($narrow)) {
                    $query->where('siding_id', $narrow);
                }
            }
        }

        /** @var array<string, mixed> $filters */
        $filters = request()->query('filter', []);
        $hasExplicitDateFilter = array_key_exists('loading_date', $filters)
            || array_key_exists('placement_time', $filters);

        if (! $hasExplicitDateFilter) {
            $today = CarbonImmutable::now()->toDateString();
            $query->whereBetween('loading_date', [$today, $today]);
        }

        return $query;
    }

    public static function tableDefaultSort(): string
    {
        return '-id';
    }

    public static function tableAllowedFilters(): array
    {
        return [
            'rake_number',
            AllowedFilter::custom('loading_date', new OperatorFilter('date')),
        ];
    }

    public static function tableAllowedSorts(): array
    {
        return ['rake_number', 'loading_date'];
    }
}
