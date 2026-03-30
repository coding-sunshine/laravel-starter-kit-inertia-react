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

final class RakeDataTable extends AbstractDataTable
{
    /** Rake data_source values that are considered system/manual (shown on /rakes). Historical and RR-import rakes are hidden. */
    private const ALLOWED_DATA_SOURCES = ['system', 'manual'];

    public function __construct(
        public int $id,
        public string $rake_number,
        public ?string $rake_type,
        public ?int $wagon_count,
        public ?string $state,
        public ?string $loading_date,
        public ?string $placement_time,
        public ?string $dispatch_time,
        public ?int $siding_id,
        public ?string $siding_code,
        public ?string $siding_name,
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
            rake_type: $model->rake_type,
            wagon_count: $model->wagon_count,
            state: $model->state,
            loading_date: $model->loading_date?->toDateString(),
            placement_time: $model->placement_time?->toIso8601String(),
            dispatch_time: $model->dispatch_time?->toIso8601String(),
            siding_id: $model->siding_id,
            siding_code: $model->siding?->code,
            siding_name: $model->siding?->name,
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
            new Column(id: 'siding_code', label: 'Siding', type: 'text', sortable: false, filterable: false),
            new Column(id: 'rake_type', label: 'Type', type: 'text', sortable: true, filterable: true),
            new Column(id: 'wagon_count', label: 'Wagons', type: 'number', sortable: true, filterable: false),
            new Column(id: 'state', label: 'State', type: 'option', sortable: true, filterable: true, options: [
                ['label' => 'Loading', 'value' => 'loading'],
                ['label' => 'Loaded', 'value' => 'loaded'],
                ['label' => 'Dispatched', 'value' => 'dispatched'],
                ['label' => 'Arrived', 'value' => 'arrived'],
                ['label' => 'Completed', 'value' => 'completed'],
            ]),
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

    public static function tableBaseQuery(): Builder
    {
        $query = Rake::query()->with([
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
        ]);

        $query->where(function (Builder $q): void {
            $q->whereNull('data_source')
                ->orWhereIn('data_source', self::ALLOWED_DATA_SOURCES);
        });

        $user = request()->user();
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
        $filters = request()->query('filter', []);
        $hasExplicitDateFilter = array_key_exists('loading_date', $filters)
            || array_key_exists('placement_time', $filters);

        if (! $hasExplicitDateFilter) {
            $monthStart = CarbonImmutable::now()->startOfMonth()->toDateString();
            $monthEnd = CarbonImmutable::now()->endOfMonth()->toDateString();

            $query->whereBetween('loading_date', [$monthStart, $monthEnd]);
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
            'state',
            'rake_type',
            AllowedFilter::custom('loading_date', new OperatorFilter('date')),
        ];
    }

    public static function tableAllowedSorts(): array
    {
        return ['rake_number', 'rake_type', 'wagon_count', 'state', 'loading_date'];
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
        $rakeWeighmentsWithPdf = $model->relationLoaded('rakeWeighments')
            ? $model->rakeWeighments->whereNotNull('pdf_file_path')
            : $model->rakeWeighments()->whereNotNull('pdf_file_path')->get();
        $isWeighmentCompleted = $rakeWeighmentsWithPdf->contains('status', 'success');
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
