<?php

declare(strict_types=1);

namespace App\DataTables;

use App\Models\Rake;
use App\Models\Siding;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Machour\DataTable\AbstractDataTable;
use Machour\DataTable\Columns\Column;
use Machour\DataTable\Filters\OperatorFilter;
use Machour\DataTable\QuickView;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\AllowedSort;

final class RakeDataTable extends AbstractDataTable
{
    /** Rake data_source values that are considered system/manual (shown on /rakes). Historical and RR-import rakes are hidden. */
    private const ALLOWED_DATA_SOURCES = ['system', 'manual'];

    public function __construct(
        public int $id,
        public string $rake_number,
        public ?string $rake_serial_number,
        public ?string $rake_type,
        public ?int $wagon_count,
        public ?string $state,
        public ?string $loading_date,
        public ?string $placement_time,
        public ?string $dispatch_time,
        public ?int $siding_id,
        public ?string $siding_code,
        public ?string $siding_name,
        public ?string $destination,
        public ?string $data_source,
        public ?int $rr_document_id,
        public ?string $pdf_download_url,
        public bool $workflow_has_pending,
        /** @var array{txr_done: bool, wagon_loading_done: bool, guard_done: bool, weighment_done: bool, rr_done: bool} */
        public array $workflow_steps,
    ) {}

    public static function fromModel(Rake $model): self
    {
        $steps = self::workflowStepsForRake($model);

        return new self(
            id: $model->id,
            rake_number: $model->rake_number,
            rake_serial_number: $model->rake_serial_number,
            rake_type: $model->rake_type,
            wagon_count: $model->wagon_count,
            state: $model->state,
            loading_date: $model->loading_date?->toDateString(),
            placement_time: $model->placement_time?->toIso8601String(),
            dispatch_time: $model->dispatch_time?->toIso8601String(),
            siding_id: $model->siding_id,
            siding_code: $model->siding?->code,
            siding_name: $model->siding?->name,
            destination: self::destinationLabel($model),
            data_source: $model->data_source,
            rr_document_id: $model->rrDocument?->id,
            pdf_download_url: $model->pdf_download_url,
            workflow_has_pending: ! ($steps['txr_done'] && $steps['wagon_loading_done'] && $steps['guard_done'] && $steps['weighment_done'] && $steps['rr_done']),
            workflow_steps: $steps,
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
            new Column(id: 'destination', label: 'Destination', type: 'text', sortable: true, filterable: false),
            new Column(id: 'loading_date', label: 'Loading date', type: 'date', sortable: true, filterable: true),
            new Column(id: 'progress', label: 'Progress', type: 'text', sortable: false, filterable: false),
        ];
    }

    public static function tableQuickViews(): array
    {
        return [
            new QuickView(
                id: 'all',
                label: 'All',
                params: [],
            ),
            new QuickView(
                id: 'loading',
                label: 'Loading',
                params: ['filter[state]' => 'eq:loading'],
                icon: 'clock',
            ),
            new QuickView(
                id: 'this_month',
                label: 'This month',
                params: ['filter[loading_date]' => 'after:'.now()->startOfMonth()->toDateString()],
                icon: 'calendar',
            ),
        ];
    }

    /**
     * Base rake list query (data_source scope, siding scope, loading_date window).
     * Matches `/rakes` DataTable and API index when combined with {@see self::rakeListEagerLoads()}.
     *
     * When `filter[loading_date]` and `filter[placement_time]` are both absent: applies legacy
     * `from_date` / `to_date` on `loading_date` if present, otherwise the current calendar month
     * on `loading_date` (same as the website default).
     */
    public static function listQueryForRequest(Request $request): Builder
    {
        $query = Rake::query();

        $query->where(function (Builder $q): void {
            $q->whereNull('data_source')
                ->orWhereIn('data_source', self::ALLOWED_DATA_SOURCES);
        });

        $user = $request->user();
        if ($user && ! $user->isSuperAdmin()) {
            $sidingIds = $user->sidings()->get()->pluck('id')->all();

            // Backward compatibility: some legacy users only have `users.siding_id`
            // and no rows in the `user_siding` pivot table.
            if ($sidingIds === [] && $user->siding_id !== null) {
                $sidingIds = [(int) $user->siding_id];
            }
            $query->whereIn('siding_id', $sidingIds);
        }

        /** @var array<string, mixed> $filters */
        $filters = $request->query('filter', []);
        $filters = is_array($filters) ? $filters : [];

        $hasExplicitDateFilter = array_key_exists('loading_date', $filters)
            || array_key_exists('placement_time', $filters);

        if (! $hasExplicitDateFilter) {
            if ($request->filled('from_date') || $request->filled('to_date')) {
                if ($request->filled('from_date')) {
                    $query->whereDate('loading_date', '>=', $request->date('from_date'));
                }
                if ($request->filled('to_date')) {
                    $query->whereDate('loading_date', '<=', $request->date('to_date'));
                }
            } else {
                $monthStart = CarbonImmutable::now()->startOfMonth()->toDateString();
                $monthEnd = CarbonImmutable::now()->endOfMonth()->toDateString();

                $query->whereBetween('loading_date', [$monthStart, $monthEnd]);
            }
        }

        return $query;
    }

    /**
     * Eager loads required for {@see self::fromModel()} and the rakes list.
     *
     * @return array<string, mixed>
     */
    public static function rakeListEagerLoads(): array
    {
        return [
            'siding:id,code,name',
            'rrDocument:id,rake_id,diverrt_destination_id',
            'rrDocument.media',
            'rrDocuments:id,rake_id,diverrt_destination_id',
            'diverrtDestinations:id,rake_id',
            'txr:id,rake_id,status,inspection_time,inspection_end_time',
            'wagons:id,rake_id,is_unfit',
            'wagonLoadings:id,rake_id,wagon_id,loaded_quantity_mt',
            'guardInspections:id,rake_id,is_approved',
            'rakeWeighments:id,rake_id,pdf_file_path,status',
        ];
    }

    public static function tableBaseQuery(): Builder
    {
        return self::listQueryForRequest(request())->with(self::rakeListEagerLoads());
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
            // Legacy bookmarked URLs may still send filter[state]; column/filter UI removed.
            AllowedFilter::callback('state', function (Builder $_query, mixed $_value): void {
                // Intentionally no query change.
            }),
            AllowedFilter::callback('siding_code', function (Builder $query, mixed $value): void {
                self::applySidingIdFilter($query, $value);
            }),
            // Legacy query params used `filter[siding]` before siding was merged into `siding_code`.
            AllowedFilter::callback('siding', function (Builder $query, mixed $value): void {
                self::applySidingIdFilter($query, $value);
            }),
        ];
    }

    public static function tableAllowedSorts(): array
    {
        return [
            'id',
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
        ];
    }

    /**
     * Workflow step completion flags (same logic as the rakes table progress column).
     *
     * @return array{txr_done: bool, wagon_loading_done: bool, guard_done: bool, weighment_done: bool, rr_done: bool}
     */
    public static function workflowStepsForRake(Rake $model): array
    {
        return self::computeWorkflowSteps($model);
    }

    /**
     * Workflow step completion flags. Mirrors RakeWorkflow progress logic.
     *
     * @return array{txr_done: bool, wagon_loading_done: bool, guard_done: bool, weighment_done: bool, rr_done: bool}
     */
    public static function rakeRrWorkflowIsComplete(Rake $model): bool
    {
        if (! $model->is_diverted) {
            return $model->relationLoaded('rrDocument')
                ? $model->rrDocument !== null
                : $model->rrDocument()->exists();
        }

        $primaryExists = $model->relationLoaded('rrDocuments')
            ? $model->rrDocuments->contains(fn ($d) => $d->diverrt_destination_id === null)
            : $model->rrDocuments()->whereNull('diverrt_destination_id')->exists();

        if (! $primaryExists) {
            return false;
        }

        $destIds = $model->relationLoaded('diverrtDestinations')
            ? $model->diverrtDestinations->pluck('id')
            : $model->diverrtDestinations()->pluck('id');

        if ($destIds->isEmpty()) {
            return true;
        }

        $covered = $model->rrDocuments()
            ->whereIn('diverrt_destination_id', $destIds)
            ->pluck('diverrt_destination_id')
            ->unique()
            ->filter();

        return $covered->count() === $destIds->count();
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

    private static function computeWorkflowSteps(Rake $model): array
    {
        $txr = $model->txr;
        // Match web RakeWorkflow TXR step: done when start + end times are saved. Keep
        // status=completed as true so legacy rows and mobile integrations stay consistent.
        // Use getAttributes() for times so partial eager-loads (missing columns) do not throw
        // MissingAttributeException on __get().
        $isTxrDone = false;
        if ($txr !== null) {
            if ($txr->status === 'completed') {
                $isTxrDone = true;
            } else {
                $attrs = $txr->getAttributes();
                if (
                    array_key_exists('inspection_time', $attrs)
                    && array_key_exists('inspection_end_time', $attrs)
                    && $attrs['inspection_time'] !== null
                    && $attrs['inspection_end_time'] !== null
                ) {
                    $isTxrDone = true;
                }
            }
        }
        $wagons = $model->relationLoaded('wagons') ? $model->wagons : $model->wagons()->get();
        $fitWagons = $wagons->filter(fn ($w) => ! $w->is_unfit);
        $wagonLoadings = $model->relationLoaded('wagonLoadings') ? $model->wagonLoadings : $model->wagonLoadings()->get();
        $positivelyLoadedWagonIds = $wagonLoadings
            ->filter(fn ($l) => (float) $l->loaded_quantity_mt > 0)
            ->pluck('wagon_id')
            ->flip();
        $isWagonLoadingCompleted = $fitWagons->isNotEmpty()
            && $fitWagons->every(fn ($w) => $positivelyLoadedWagonIds->has($w->id));
        $guardInspections = $model->relationLoaded('guardInspections') ? $model->guardInspections : $model->guardInspections()->get();
        $isGuardApproved = $guardInspections->isNotEmpty() && $guardInspections->first()?->is_approved === true;
        $rakeWeighments = $model->relationLoaded('rakeWeighments')
            ? $model->rakeWeighments
            : $model->rakeWeighments()->get();
        $isWeighmentCompleted = $rakeWeighments->isNotEmpty();
        $hasRrDocument = $model->relationLoaded('rrDocument')
            ? $model->rrDocument !== null
            : $model->rrDocument()->exists();

        return [
            'txr_done' => $isTxrDone,
            'wagon_loading_done' => $isWagonLoadingCompleted,
            'guard_done' => $isGuardApproved,
            'weighment_done' => $isWeighmentCompleted,
            'rr_done' => $hasRrDocument,
        ];
    }
}
