<?php

declare(strict_types=1);

namespace App\DataTables;

use App\Models\RrDocument;
use Illuminate\Database\Eloquent\Builder;
use Machour\DataTable\AbstractDataTable;
use Machour\DataTable\Columns\Column;
use Machour\DataTable\Filters\OperatorFilter;
use Machour\DataTable\QuickView;
use Spatie\QueryBuilder\AllowedFilter;

final class RailwayReceiptsStandaloneRrDataTable extends AbstractDataTable
{
    public function __construct(
        public int $id,
        public string $rr_number,
        public string $rr_received_date,
        public ?string $rr_weight_mt,
    ) {}

    public static function fromModel(RrDocument $model): self
    {
        return new self(
            id: $model->id,
            rr_number: $model->rr_number,
            rr_received_date: $model->rr_received_date?->format('Y-m-d') ?? '',
            rr_weight_mt: $model->rr_weight_mt !== null ? (string) $model->rr_weight_mt : null,
        );
    }

    public static function tableColumns(): array
    {
        return [
            new Column(id: 'rr_number', label: 'RR number', type: 'text', sortable: true, filterable: true),
            new Column(id: 'rr_received_date', label: 'Received date', type: 'date', sortable: true, filterable: true),
            new Column(id: 'rr_weight_mt', label: 'Weight (MT)', type: 'number', sortable: true, filterable: true),
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

        return RrDocument::query()
            ->whereNull('rake_id')
            ->when(
                $user && ! $user->isSuperAdmin(),
                fn (Builder $query): Builder => $query->where('created_by', (int) $user->id),
            );
    }

    public static function tableDefaultSort(): string
    {
        return '-rr_received_date';
    }

    public static function tableAllowedFilters(): array
    {
        return [
            AllowedFilter::custom('rr_number', new OperatorFilter('text')),
            AllowedFilter::custom('rr_received_date', new OperatorFilter('date')),
            AllowedFilter::custom('rr_weight_mt', new OperatorFilter('number')),
        ];
    }

    public static function tableAllowedSorts(): array
    {
        return ['rr_number', 'rr_received_date', 'rr_weight_mt'];
    }
}
