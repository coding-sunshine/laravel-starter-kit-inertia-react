<?php

declare(strict_types=1);

namespace App\DataTables;

use App\Actions\ReconcileRakeAction;
use App\Models\Rake;
use App\Models\Siding;
use Illuminate\Database\Eloquent\Builder;
use Machour\DataTable\AbstractDataTable;
use Machour\DataTable\Columns\Column;
use Machour\DataTable\Filters\OperatorFilter;
use Machour\DataTable\QuickView;
use Spatie\QueryBuilder\AllowedFilter;

final class ReconciliationDataTable extends AbstractDataTable
{
    public function __construct(
        public int $id,
        public string $rake_number,
        public ?string $siding_name,
        public string $overall_status,
    ) {}

    public static function fromModel(Rake $model): self
    {
        $points = resolve(ReconcileRakeAction::class)->handle($model);
        $worst = collect($points)->max('status') === 'MAJOR_DIFF'
            ? 'MAJOR_DIFF'
            : (collect($points)->contains('status', 'MINOR_DIFF') ? 'MINOR_DIFF' : 'MATCH');

        return new self(
            id: $model->id,
            rake_number: $model->rake_number,
            siding_name: $model->siding?->name,
            overall_status: $worst,
        );
    }

    public static function tableColumns(): array
    {
        return [
            new Column(id: 'rake_number', label: 'Rake', type: 'text', sortable: true, filterable: false),
            new Column(id: 'siding_name', label: 'Siding', type: 'text', sortable: false, filterable: false),
            new Column(id: 'overall_status', label: 'Status', type: 'text', sortable: false, filterable: false),
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
        $user = request()->user();
        $sidingIds = $user && $user->isSuperAdmin()
            ? Siding::query()->pluck('id')->all()
            : ($user ? $user->accessibleSidings()->get()->pluck('id')->all() : []);

        return Rake::query()
            ->with('siding:id,name,code')
            ->whereIn('siding_id', $sidingIds)
            ->whereHas(
                'rakeWeighments',
                static fn ($q) => $q->where('status', 'success')
            );
    }

    public static function tableDefaultSort(): string
    {
        return '-loading_end_time';
    }

    public static function tableAllowedSorts(): array
    {
        return ['rake_number', 'loading_end_time'];
    }

    public static function tableAllowedFilters(): array
    {
        return [
            AllowedFilter::custom('siding_id', new OperatorFilter('number')),
        ];
    }
}
