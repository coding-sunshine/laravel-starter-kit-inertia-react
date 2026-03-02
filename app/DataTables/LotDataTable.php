<?php

namespace App\DataTables;

use App\Models\Lot;
use Illuminate\Database\Eloquent\Builder;
use Machour\DataTable\AbstractDataTable;
use Machour\DataTable\Columns\Column;
use Machour\DataTable\QuickView;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
class LotDataTable extends AbstractDataTable
{
    public function __construct(
        public int $id,
        // TODO: Add DTO properties matching your model
        public ?string $created_at,
    ) {}

    public static function fromModel(Lot $model): self
    {
        return new self(
            id: $model->id,
            created_at: $model->created_at?->format('Y-m-d H:i'),
        );
    }

    public static function tableColumns(): array
    {
        return [
            new Column(id: 'id', label: 'ID', type: 'number', sortable: true),
            // TODO: Add columns matching your DTO properties
            new Column(id: 'created_at', label: 'Created at', type: 'date', sortable: true),
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
            // TODO: Add quick views for common filters
        ];
    }

    public static function tableBaseQuery(): Builder
    {
        return Lot::query();
    }

    public static function tableDefaultSort(): string
    {
        return '-id';
    }
}