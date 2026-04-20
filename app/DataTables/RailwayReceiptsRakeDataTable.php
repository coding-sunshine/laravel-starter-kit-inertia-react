<?php

declare(strict_types=1);

namespace App\DataTables;

use App\Models\Rake;
use App\Models\RrDocument;
use App\Models\Siding;
use Illuminate\Database\Eloquent\Builder;
use Machour\DataTable\AbstractDataTable;
use Machour\DataTable\Columns\Column;
use Machour\DataTable\Filters\OperatorFilter;
use Machour\DataTable\QuickView;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\AllowedSort;

final class RailwayReceiptsRakeDataTable extends AbstractDataTable
{
    /** Same non-historical scope as {@see WeighmentsRakeDataTable}. */
    private const ALLOWED_DATA_SOURCES = ['system', 'manual'];

    public function __construct(
        public int $id,
        public string $rake_number,
        public ?string $rake_serial_number,
        public ?string $loading_date,
        public ?int $siding_id,
        public ?string $siding_code,
        public ?string $siding_name,
        public ?string $destination,
        public bool $has_diversion,
        public ?int $rr_document_id,
        public ?string $rr_number,
        public ?string $rr_received_date,
        public ?string $rr_weight_mt,
        public ?string $document_status,
        public ?bool $has_discrepancy,
        public ?string $discrepancy_details,
        public ?string $fnr,
        public ?string $from_station_code,
        public ?string $to_station_code,
        public ?string $freight_total,
        public ?string $distance_km,
        public ?string $commodity_code,
        public ?string $commodity_description,
        public ?string $invoice_number,
        public ?string $invoice_date,
        public ?string $rate,
        public ?string $document_class,
    ) {}

    public static function fromModel(Rake $model): self
    {
        $doc = $model->rrDocument;
        $hasDiversion = ((int) ($model->diverrt_destinations_count ?? 0)) > 0;

        return new self(
            id: $model->id,
            rake_number: $model->rake_number,
            rake_serial_number: $model->rake_serial_number,
            loading_date: $model->loading_date?->toDateString(),
            siding_id: $model->siding_id,
            siding_code: $model->siding?->code,
            siding_name: $model->siding?->name,
            destination: self::destinationLabel($model),
            has_diversion: $hasDiversion,
            rr_document_id: $doc !== null ? (int) $doc->getKey() : null,
            rr_number: $doc !== null ? $doc->rr_number : null,
            rr_received_date: $doc?->rr_received_date?->format('Y-m-d'),
            rr_weight_mt: $doc !== null && $doc->rr_weight_mt !== null ? (string) $doc->rr_weight_mt : null,
            document_status: $doc?->document_status,
            has_discrepancy: $doc !== null ? (bool) $doc->has_discrepancy : null,
            discrepancy_details: $doc?->discrepancy_details,
            fnr: $doc?->fnr,
            from_station_code: $doc?->from_station_code,
            to_station_code: $doc?->to_station_code,
            freight_total: $doc !== null && $doc->freight_total !== null ? (string) $doc->freight_total : null,
            distance_km: $doc !== null && $doc->distance_km !== null ? (string) $doc->distance_km : null,
            commodity_code: $doc?->commodity_code,
            commodity_description: $doc?->commodity_description,
            invoice_number: $doc?->invoice_number,
            invoice_date: $doc?->invoice_date?->toDateString(),
            rate: $doc !== null && $doc->rate !== null ? (string) $doc->rate : null,
            document_class: $doc !== null ? ($doc->getAttribute('class') !== null ? (string) $doc->getAttribute('class') : null) : null,
        );
    }

    public static function tableColumns(): array
    {
        return [
            new Column(id: 'rake_number', label: 'Rake #', type: 'text', sortable: true, filterable: true),
            new Column(id: 'rake_serial_number', label: 'Rake Number', type: 'text', sortable: false, filterable: false),
            new Column(
                id: 'siding_code',
                label: 'Siding',
                type: 'option',
                sortable: true,
                filterable: true,
                options: self::filterableSidingOptions(),
            ),
            new Column(id: 'destination', label: 'Destination', type: 'text', sortable: true, filterable: true),
            new Column(id: 'loading_date', label: 'Loading date', type: 'date', sortable: true, filterable: true),
            new Column(id: 'rr_number', label: 'RR number', type: 'text', sortable: true, filterable: true),
            new Column(id: 'rr_received_date', label: 'Received date', type: 'date', sortable: true, filterable: false),
            new Column(id: 'rr_weight_mt', label: 'Weight (MT)', type: 'number', sortable: true, filterable: false),
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
        $query = Rake::query()
            ->withCount('diverrtDestinations')
            ->with([
                'siding:id,code,name',
                'rrDocument:id,rake_id,diverrt_destination_id,rr_number,rr_received_date,rr_weight_mt,document_status,has_discrepancy,discrepancy_details,fnr,from_station_code,to_station_code,freight_total,distance_km,commodity_code,commodity_description,invoice_number,invoice_date,rate,class',
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
                $query->whereIn('siding_id', $sidingIds);
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
            AllowedFilter::callback('siding_code', function (Builder $query, mixed $value): void {
                self::applySidingIdFilter($query, $value);
            }),
            AllowedFilter::callback('siding', function (Builder $query, mixed $value): void {
                self::applySidingIdFilter($query, $value);
            }),
            AllowedFilter::custom('destination', new OperatorFilter('text')),
            AllowedFilter::callback('rr_number', function (Builder $query, mixed $value): void {
                $query->whereHas('rrDocument', function (Builder $q) use ($value): void {
                    (new OperatorFilter('text'))($q, $value, 'rr_number');
                });
            }),
        ];
    }

    public static function tableAllowedSorts(): array
    {
        return [
            'rake_number',
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
            AllowedSort::callback('rr_number', static function (Builder $query, bool $descending, string $_property): void {
                self::orderByPrimaryRrColumn($query, $descending, 'rr_number');
            }),
            AllowedSort::callback('rr_received_date', static function (Builder $query, bool $descending, string $_property): void {
                self::orderByPrimaryRrColumn($query, $descending, 'rr_received_date');
            }),
            AllowedSort::callback('rr_weight_mt', static function (Builder $query, bool $descending, string $_property): void {
                self::orderByPrimaryRrColumn($query, $descending, 'rr_weight_mt');
            }),
        ];
    }

    private static function orderByPrimaryRrColumn(Builder $query, bool $descending, string $column): void
    {
        $direction = $descending ? 'desc' : 'asc';
        $rakesTable = $query->getModel()->getTable();
        $query->orderBy(
            RrDocument::query()
                ->select($column)
                ->whereColumn('rake_id', "{$rakesTable}.id")
                ->whereNull('diverrt_destination_id')
                ->orderByDesc('id')
                ->limit(1),
            $direction
        );
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

        if ($operator === 'in') {
            $ids = array_map(static fn (string $v): int => (int) $v, $values);
            $query->whereIn('siding_id', $ids);

            return;
        }

        if ($operator === 'not_in') {
            $ids = array_map(static fn (string $v): int => (int) $v, $values);
            $query->whereNotIn('siding_id', $ids);

            return;
        }

        if ($operator === 'eq') {
            $query->where('siding_id', (int) $values[0]);

            return;
        }

        $needle = $values[0];
        $query->whereHas('siding', static function (Builder $sidingQuery) use ($needle): void {
            $sidingQuery->where('code', 'LIKE', '%'.$needle.'%')
                ->orWhere('name', 'LIKE', '%'.$needle.'%');
        });
    }
}
