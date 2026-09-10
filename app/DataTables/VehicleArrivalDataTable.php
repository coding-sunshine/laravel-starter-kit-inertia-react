<?php

declare(strict_types=1);

namespace App\DataTables;

use App\Models\Siding;
use App\Models\VehicleArrival;
use Illuminate\Database\Eloquent\Builder;
use Machour\DataTable\AbstractDataTable;
use Machour\DataTable\Columns\Column;
use Machour\DataTable\Filters\OperatorFilter;
use Machour\DataTable\QuickView;
use Spatie\QueryBuilder\AllowedFilter;

final class VehicleArrivalDataTable extends AbstractDataTable
{
    public function __construct(
        public int $id,
        public int $siding_id,
        public int $vehicle_id,
        public string $status,
        public string $arrived_at,
        public ?string $net_weight,
        public ?string $unloaded_quantity,
        public ?string $siding_code,
        public ?string $siding_name,
        public ?string $vehicle_number,
    ) {}

    public static function fromModel(VehicleArrival $model): self
    {
        return new self(
            id: $model->id,
            siding_id: $model->siding_id,
            vehicle_id: $model->vehicle_id,
            status: $model->status,
            arrived_at: $model->arrived_at?->toIso8601String() ?? '',
            net_weight: $model->net_weight !== null ? (string) $model->net_weight : null,
            unloaded_quantity: $model->unloaded_quantity !== null ? (string) $model->unloaded_quantity : null,
            siding_code: $model->siding?->code,
            siding_name: $model->siding?->name,
            vehicle_number: $model->vehicle?->vehicle_number,
        );
    }

    public static function tableColumns(): array
    {
        return [
            new Column(id: 'arrived_at', label: 'Arrived at', type: 'date', sortable: true, filterable: true),
            new Column(id: 'siding_code', label: 'Siding', type: 'text', sortable: false, filterable: false),
            new Column(id: 'vehicle_number', label: 'Vehicle', type: 'text', sortable: false, filterable: false),
            new Column(id: 'status', label: 'Status', type: 'option', sortable: true, filterable: true, options: [
                ['label' => 'Pending', 'value' => 'pending'],
                ['label' => 'Unloading', 'value' => 'unloading'],
                ['label' => 'Unloaded', 'value' => 'unloaded'],
                ['label' => 'Completed', 'value' => 'completed'],
                ['label' => 'Cancelled', 'value' => 'cancelled'],
            ]),
            new Column(id: 'net_weight', label: 'Net (MT)', type: 'number', sortable: false, filterable: false),
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
                id: 'today',
                label: 'Today',
                params: ['filter[arrived_at]' => 'after:'.now()->toDateString()],
                icon: 'calendar',
            ),
            new QuickView(
                id: 'last_7_days',
                label: 'Last 7 days',
                params: ['filter[arrived_at]' => 'after:'.now()->subDays(7)->toDateString()],
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

        $query = VehicleArrival::query()
            ->with(['siding:id,name,code', 'vehicle:id,vehicle_number,owner_name'])
            ->whereIn('siding_id', $sidingIds);

        return $query;
    }

    public static function tableDefaultSort(): string
    {
        return '-arrived_at';
    }

    public static function tableAllowedFilters(): array
    {
        return [
            AllowedFilter::custom('arrived_at', new OperatorFilter('date')),
            AllowedFilter::custom('siding_id', new OperatorFilter('number')),
            AllowedFilter::custom('status', new OperatorFilter('option')),
        ];
    }

    public static function tableAllowedSorts(): array
    {
        return ['arrived_at', 'status'];
    }
}
