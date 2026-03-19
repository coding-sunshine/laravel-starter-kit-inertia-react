<?php

declare(strict_types=1);

namespace App\DataTables;

use App\Models\Rake;
use Illuminate\Database\Eloquent\Builder;
use Machour\DataTable\AbstractDataTable;
use Machour\DataTable\Columns\Column;
use Machour\DataTable\QuickView;

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
            new Column(id: 'progress', label: 'Progress', type: 'text', sortable: false, filterable: false),
            new Column(id: 'placement_time', label: 'Loading window', type: 'date', sortable: true, filterable: true),
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
                id: 'recent',
                label: 'Last 7 days',
                params: ['filter[placement_time]' => 'after:'.now()->subDays(7)->toDateString()],
                icon: 'calendar',
            ),
        ];
    }

    public static function tableBaseQuery(): Builder
    {
        $query = Rake::query()->with([
            'siding:id,code,name',
            'rrDocument:id,rake_id',
            'rrDocument.media',
            'txr:id,rake_id,status',
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
            $query->whereIn('siding_id', $sidingIds);
        }

        return $query;
    }

    public static function tableDefaultSort(): string
    {
        return '-id';
    }

    public static function tableAllowedFilters(): array
    {
        return ['rake_number', 'state', 'rake_type', 'siding_id', 'placement_time'];
    }

    public static function tableAllowedSorts(): array
    {
        return ['rake_number', 'rake_type', 'wagon_count', 'state', 'placement_time'];
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
    private static function computeWorkflowSteps(Rake $model): array
    {
        $isTxrCompleted = $model->txr?->status === 'completed';
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
            'txr_done' => $isTxrCompleted,
            'wagon_loading_done' => $isWagonLoadingCompleted,
            'guard_done' => $isGuardApproved,
            'weighment_done' => $isWeighmentCompleted,
            'rr_done' => $hasRrDocument,
        ];
    }
}
