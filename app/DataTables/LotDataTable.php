<?php

declare(strict_types=1);

namespace App\DataTables;

use App\Models\Lot;
use Illuminate\Database\Eloquent\Builder;
use Machour\DataTable\AbstractDataTable;
use Machour\DataTable\Columns\Column;
use Machour\DataTable\QuickView;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
final class LotDataTable extends AbstractDataTable
{
    public function __construct(
        public int $id,
        public ?string $title,
        public ?string $project_title,
        public ?string $price,
        public ?string $land_price,
        public ?string $stage,
        public ?int $bedrooms,
        public ?int $bathrooms,
        public ?string $land_size,
        public bool $is_archived,
        public ?string $created_at,
    ) {}

    public static function fromModel(Lot $model): self
    {
        return new self(
            id: $model->id,
            title: $model->title,
            project_title: $model->project?->title,
            price: $model->price !== null ? (string) $model->price : null,
            land_price: $model->land_price !== null ? (string) $model->land_price : null,
            stage: $model->stage,
            bedrooms: $model->bedrooms,
            bathrooms: $model->bathrooms,
            land_size: $model->land_size !== null ? (string) $model->land_size : null,
            is_archived: (bool) $model->is_archived,
            created_at: $model->created_at?->format('Y-m-d H:i'),
        );
    }

    public static function tableColumns(): array
    {
        return [
            new Column(id: 'id', label: 'ID', type: 'number', sortable: true),
            new Column(id: 'title', label: 'Title', type: 'string', sortable: true),
            new Column(id: 'project_title', label: 'Project', type: 'string', sortable: false),
            new Column(id: 'price', label: 'Price', type: 'string', sortable: true),
            new Column(id: 'land_price', label: 'Land price', type: 'string', sortable: true),
            new Column(id: 'stage', label: 'Stage', type: 'string', sortable: true),
            new Column(id: 'bedrooms', label: 'Beds', type: 'number', sortable: true),
            new Column(id: 'bathrooms', label: 'Baths', type: 'number', sortable: true),
            new Column(id: 'land_size', label: 'Land size', type: 'string', sortable: true),
            new Column(id: 'is_archived', label: 'Archived', type: 'boolean', sortable: true),
            new Column(id: 'created_at', label: 'Created at', type: 'date', sortable: true),
        ];
    }

    public static function tableQuickViews(): array
    {
        return [
            new QuickView(id: 'all', label: 'All', params: []),
            new QuickView(id: 'active', label: 'Active', params: ['filter[is_archived]' => '0']),
            new QuickView(id: 'archived', label: 'Archived', params: ['filter[is_archived]' => '1']),
        ];
    }

    public static function tableBaseQuery(): Builder
    {
        return Lot::query()
            ->with(['project']);
    }

    public static function tableDefaultSort(): string
    {
        return '-id';
    }
}
