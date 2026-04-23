<?php

declare(strict_types=1);

namespace App\DataTables;

use App\Models\Indent;
use App\Models\Rake;
use App\Models\RakeWeighment;
use App\Models\Siding;
use Illuminate\Database\Eloquent\Builder;
use Machour\DataTable\AbstractDataTable;
use Machour\DataTable\Columns\Column;
use Machour\DataTable\Filters\OperatorFilter;
use Machour\DataTable\QuickView;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\AllowedSort;

final class WeighmentsRakeDataTable extends AbstractDataTable
{
    /** Same non-historical scope as {@see RakeDataTable}. */
    private const ALLOWED_DATA_SOURCES = ['system', 'manual'];

    public function __construct(
        public int $id,
        public string $rake_number,
        public ?string $rake_serial_number,
        /** `indents.indent_number` when rake is linked to an indent (Priority number column). */
        public ?string $indent_number,
        public ?string $loading_date,
        public ?int $siding_id,
        public ?string $siding_code,
        public ?string $siding_name,
        public ?string $destination,
        /** Raw `rakes.destination_code` — manual tab prefill when no weighment row. */
        public ?string $rake_destination_code,
        /** Raw `rakes.priority_number` — manual tab prefill when no weighment row. */
        public ?int $rake_priority_number,
        /** `missing` | `manual_only` | `complete` */
        public string $weighment_row_state,
        public ?int $latest_weighment_id,
        public ?int $latest_attempt_no,
        public ?string $latest_total_net_weight_mt,
        public ?string $latest_total_gross_weight_mt,
        public ?string $latest_total_tare_weight_mt,
        public ?string $latest_from_station,
        public ?string $latest_to_station,
        public ?string $latest_priority_number,
        public int $latest_wagon_weighments_count,
        public bool $latest_has_pdf_path,
    ) {}

    public static function fromModel(Rake $model): self
    {
        $latest = self::latestRakeWeighment($model);

        return new self(
            id: $model->id,
            rake_number: $model->rake_number,
            rake_serial_number: self::nullableTrimmedString($model->rake_serial_number),
            indent_number: self::nullableTrimmedString($model->indent?->indent_number),
            loading_date: $model->loading_date?->toDateString(),
            siding_id: $model->siding_id,
            siding_code: $model->siding?->code,
            siding_name: $model->siding?->name,
            destination: self::destinationLabel($model),
            rake_destination_code: self::nullableTrimmedString($model->destination_code),
            rake_priority_number: $model->priority_number !== null ? (int) $model->priority_number : null,
            weighment_row_state: self::weighmentRowState($model),
            latest_weighment_id: self::latestWeighmentId($model),
            latest_attempt_no: $latest?->attempt_no !== null ? (int) $latest->attempt_no : null,
            latest_total_net_weight_mt: self::decimalStringOrNull($latest?->total_net_weight_mt),
            latest_total_gross_weight_mt: self::decimalStringOrNull($latest?->total_gross_weight_mt),
            latest_total_tare_weight_mt: self::decimalStringOrNull($latest?->total_tare_weight_mt),
            latest_from_station: self::nullableTrimmedString($latest?->from_station),
            latest_to_station: self::nullableTrimmedString($latest?->to_station),
            latest_priority_number: self::nullableTrimmedString($latest?->priority_number),
            latest_wagon_weighments_count: $latest !== null
                ? (int) ($latest->rake_wagon_weighments_count ?? 0)
                : 0,
            latest_has_pdf_path: $latest !== null && self::hasPdfPath($latest),
        );
    }

    public static function tableColumns(): array
    {
        return [
            new Column(id: 'rake_number', label: 'Rake #', type: 'text', sortable: true, filterable: true),
            new Column(id: 'rake_serial_number', label: 'Rake Number', type: 'text', sortable: false, filterable: false),
            new Column(
                id: 'indent_number',
                label: 'Priority number',
                type: 'text',
                sortable: true,
                filterable: false,
            ),
            new Column(
                id: 'siding_code',
                label: 'Siding',
                type: 'option',
                sortable: true,
                filterable: true,
                options: self::filterableSidingOptions(),
            ),
            new Column(id: 'destination', label: 'Destination', type: 'text', sortable: true, filterable: true),
            new Column(
                id: 'latest_total_net_weight_mt',
                label: 'Net weighment',
                type: 'text',
                sortable: false,
                filterable: false,
            ),
            new Column(id: 'loading_date', label: 'Loading date', type: 'date', sortable: true, filterable: true),
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
        $query = Rake::query()->with([
            'siding:id,code,name',
            'indent:id,indent_number',
            'rakeWeighments' => static function ($relation): void {
                $relation
                    ->select([
                        'id',
                        'rake_id',
                        'attempt_no',
                        'pdf_file_path',
                        'from_station',
                        'to_station',
                        'priority_number',
                        'total_gross_weight_mt',
                        'total_tare_weight_mt',
                        'total_net_weight_mt',
                    ])
                    ->withCount('rakeWagonWeighments')
                    ->orderByDesc('id');
            },
        ]);

        $query->where(function (Builder $q): void {
            $q->whereNull('data_source')
                ->orWhereIn('data_source', self::ALLOWED_DATA_SOURCES);
        });

        $user = request()->user();
        if ($user && ! $user->isSuperAdmin()) {
            $sidingIds = $user->sidings()->get()->pluck('id')->all();

            if ($sidingIds === [] && $user->siding_id !== null) {
                $sidingIds = [(int) $user->siding_id];
            }

            if ($sidingIds === []) {
                $query->whereRaw('0 = 1');
            } else {
                $query->whereIn($query->qualifyColumn('siding_id'), $sidingIds);
            }
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
            AllowedFilter::custom('rake_number', new OperatorFilter('text')),
            AllowedFilter::custom('loading_date', new OperatorFilter('date')),
            AllowedFilter::callback('state', function (Builder $_query, mixed $_value): void {
                // Legacy bookmarked URLs; no column on this hub.
            }),
            AllowedFilter::callback('siding_code', function (Builder $query, mixed $value): void {
                self::applySidingIdFilter($query, $value);
            }),
            AllowedFilter::callback('siding', function (Builder $query, mixed $value): void {
                self::applySidingIdFilter($query, $value);
            }),
            AllowedFilter::custom('destination', new OperatorFilter('text')),
        ];
    }

    public static function tableAllowedSorts(): array
    {
        return [
            'rake_number',
            AllowedSort::callback('indent_number', static function (Builder $query, bool $descending, string $_property): void {
                $direction = $descending ? 'desc' : 'asc';
                $rakesTable = $query->getModel()->getTable();
                $indentsTable = (new Indent)->getTable();
                $query->leftJoin($indentsTable, "{$indentsTable}.id", '=', "{$rakesTable}.indent_id")
                    ->select("{$rakesTable}.*")
                    ->orderBy("{$indentsTable}.indent_number", $direction);
            }),
            AllowedSort::callback('siding_code', static function (Builder $query, bool $descending, string $_property): void {
                $direction = $descending ? 'desc' : 'asc';
                $rakesTable = $query->getModel()->getTable();
                $sidingsTable = (new Siding())->getTable();
                $query->leftJoin($sidingsTable, "{$sidingsTable}.id", '=', "{$rakesTable}.siding_id")
                    ->select("{$rakesTable}.*")
                    ->orderBy("{$sidingsTable}.name", $direction)
                    ->orderBy("{$sidingsTable}.code", $direction);
            }),
            AllowedSort::callback('destination', static function (Builder $query, bool $descending, string $_property): void {
                $direction = $descending ? 'desc' : 'asc';
                $query->orderBy($query->qualifyColumn('destination'), $direction)
                    ->orderBy($query->qualifyColumn('destination_code'), $direction);
            }),
            'loading_date',
        ];
    }

    /**
     * Align with rake workflow: “documented” when a stored slip path exists or wagon lines were imported (PDF/XLSX).
     */
    private static function weighmentRowState(Rake $model): string
    {
        if ($model->rakeWeighments->isEmpty()) {
            return 'missing';
        }

        foreach ($model->rakeWeighments as $weighment) {
            $wagonCount = (int) ($weighment->rake_wagon_weighments_count ?? 0);
            if (self::hasPdfPath($weighment) || $wagonCount > 0) {
                return 'complete';
            }
        }

        return 'manual_only';
    }

    private static function latestWeighmentId(Rake $model): ?int
    {
        $latest = self::latestRakeWeighment($model);

        return $latest !== null ? (int) $latest->getKey() : null;
    }

    private static function latestRakeWeighment(Rake $model): ?RakeWeighment
    {
        if ($model->rakeWeighments->isEmpty()) {
            return null;
        }

        /** @var RakeWeighment|null $latest */
        $latest = $model->rakeWeighments->sortByDesc('id')->first();

        return $latest;
    }

    private static function hasPdfPath(RakeWeighment $weighment): bool
    {
        $path = $weighment->pdf_file_path;

        return $path !== null && mb_trim((string) $path) !== '';
    }

    private static function decimalStringOrNull(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        return is_numeric($value) ? (string) $value : null;
    }

    private static function nullableTrimmedString(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $s = mb_trim((string) $value);

        return $s === '' ? null : $s;
    }

    private static function destinationLabel(Rake $model): ?string
    {
        $code = $model->destination_code !== null && $model->destination_code !== ''
            ? mb_trim((string) $model->destination_code)
            : null;
        $name = $model->destination !== null && $model->destination !== ''
            ? mb_trim((string) $model->destination)
            : null;

        if ($code !== null && $name !== null) {
            return $code === $name ? $name : $code.' — '.$name;
        }

        return $code ?? $name;
    }

    /**
     * @return list<array{label: string, value: string}>
     */
    private static function filterableSidingOptions(): array
    {
        $user = request()->user();
        $sidingIds = $user && $user->isSuperAdmin()
            ? Siding::query()->orderBy('name')->pluck('id')->all()
            : ($user ? $user->sidings()->orderBy('name')->get()->pluck('id')->all() : []);

        if ($user && ! $user->isSuperAdmin() && $sidingIds === [] && $user->siding_id !== null) {
            $sidingIds = [(int) $user->siding_id];
        }

        if ($sidingIds === []) {
            return [];
        }

        $sidings = Siding::query()
            ->whereIn('id', $sidingIds)
            ->orderBy('name')
            ->get(['id', 'name', 'code']);

        $options = [];
        foreach ($sidings as $siding) {
            $label = $siding->name;
            if ($siding->code) {
                $label .= ' ('.$siding->code.')';
            }
            $options[] = [
                'label' => $label,
                'value' => (string) $siding->id,
            ];
        }

        return $options;
    }

    private static function applySidingIdFilter(Builder $query, mixed $value): void
    {
        $raw = is_array($value) ? implode(',', $value) : (string) $value;
        $operator = 'contains';
        $rawValue = $raw;

        if (preg_match('/^([a-z_]+):(.+)$/i', $raw, $matches)) {
            $known = ['eq', 'contains', 'in', 'not_in'];
            if (in_array($matches[1], $known, true)) {
                $operator = $matches[1];
                $rawValue = $matches[2];
            }
        }

        $values = array_values(array_filter(explode(',', $rawValue), static fn (string $v): bool => $v !== ''));
        if ($values === []) {
            return;
        }

        $sidingIdColumn = $query->qualifyColumn('siding_id');

        if ($operator === 'in') {
            $ids = array_map(static fn (string $v): int => (int) $v, $values);
            $query->whereIn($sidingIdColumn, $ids);

            return;
        }

        if ($operator === 'not_in') {
            $ids = array_map(static fn (string $v): int => (int) $v, $values);
            $query->whereNotIn($sidingIdColumn, $ids);

            return;
        }

        if ($operator === 'eq') {
            $query->where($sidingIdColumn, (int) $values[0]);

            return;
        }

        $needle = $values[0];
        $query->whereHas('siding', static function (Builder $sidingQuery) use ($needle): void {
            $sidingQuery->where('code', 'LIKE', '%'.$needle.'%')
                ->orWhere('name', 'LIKE', '%'.$needle.'%');
        });
    }
}
