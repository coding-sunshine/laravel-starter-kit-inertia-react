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
    public function __construct(
        public int $id,
        public string $rake_number,
        public ?string $rake_type,
        public int $wagon_count,
        public string $state,
        public ?string $placement_time,
        public ?string $dispatch_time,
        public ?int $siding_id,
        public ?string $siding_code,
        public ?string $siding_name,
    ) {}

    public static function fromModel(Rake $model): self
    {
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
            ]),
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
        $query = Rake::query()->with('siding:id,code,name');

        $user = request()->user();
        if ($user && ! $user->isSuperAdmin()) {
            $sidingIds = $user->sidings()->get()->pluck('id')->all();
            $query->whereIn('siding_id', $sidingIds);
        }

        return $query;
    }

    public static function tableDefaultSort(): string
    {
        return '-placement_time';
    }

    public static function tableAllowedFilters(): array
    {
        return ['rake_number', 'state', 'rake_type', 'siding_id', 'placement_time'];
    }

    public static function tableAllowedSorts(): array
    {
        return ['rake_number', 'rake_type', 'wagon_count', 'state', 'placement_time'];
    }
}
