<?php

declare(strict_types=1);

namespace App\DataTables;

use App\Models\Indent;
use App\Models\Rake;
use App\Models\Siding;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Machour\DataTable\AbstractDataTable;
use Machour\DataTable\Columns\Column;
use Machour\DataTable\Filters\OperatorFilter;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\AllowedSort;

final class IndentDataTable extends AbstractDataTable
{
    public function __construct(
        public int $id,
        public ?string $rake_number,
        public ?string $indent_number,
        public ?string $siding,
        public ?string $indent_date,
        public ?string $expected_loading_date,
        public ?string $e_demand_reference_id,
        public ?string $fnr_number,
        public ?string $state,
        public bool $weighment_pdf_uploaded,
    ) {}

    public static function fromModel(Indent $model): self
    {
        $rake = $model->rake;
        $hasWeighmentPdf = false;

        if ($rake !== null) {
            if ($rake->relationLoaded('rakeWeighments')) {
                $hasWeighmentPdf = $rake->rakeWeighments->isNotEmpty();
            } else {
                $hasWeighmentPdf = $rake->rakeWeighments()->exists();
            }
        }

        $sidingLabel = null;
        if ($model->siding !== null) {
            $code = $model->siding->code;
            $name = $model->siding->name;
            $sidingLabel = $code ? "{$code} ({$name})" : $name;
        }

        return new self(
            id: $model->id,
            rake_number: $rake?->rake_number,
            indent_number: $model->indent_number,
            siding: $sidingLabel,
            indent_date: $model->indent_date?->toDateString(),
            expected_loading_date: $model->expected_loading_date?->toDateString(),
            e_demand_reference_id: $model->e_demand_reference_id,
            fnr_number: $model->fnr_number,
            state: $model->state,
            weighment_pdf_uploaded: $hasWeighmentPdf,
        );
    }

    public static function tableColumns(): array
    {
        $user = request()->user();
        $sidingIds = $user && $user->isSuperAdmin()
            ? Siding::query()->orderBy('name')->pluck('id')->all()
            : ($user ? $user->sidings()->orderBy('name')->get()->pluck('id')->all() : []);

        if ($user && ! $user->isSuperAdmin() && $sidingIds === [] && $user->siding_id !== null) {
            $sidingIds = [(int) $user->siding_id];
        }

        /** @var list<array{label: string, value: string}> $sidingFilterOptions */
        $sidingFilterOptions = [];
        if ($sidingIds !== []) {
            $sidings = Siding::query()
                ->whereIn('id', $sidingIds)
                ->orderBy('name')
                ->get(['id', 'name', 'code']);
            foreach ($sidings as $siding) {
                $label = $siding->name;
                if ($siding->code) {
                    $label .= ' ('.$siding->code.')';
                }
                $sidingFilterOptions[] = [
                    'label' => $label,
                    'value' => (string) $siding->id,
                ];
            }
        }

        return [
            new Column(id: 'rake_number', label: 'Rake #', type: 'text', sortable: true, filterable: false),
            new Column(id: 'indent_number', label: 'E-Demand number', type: 'text', sortable: true, filterable: false),
            new Column(id: 'indent_date', label: 'Indent date', type: 'date', sortable: true, filterable: true),
            new Column(id: 'fnr_number', label: 'FNR', type: 'text', sortable: true, filterable: true),
            new Column(id: 'siding', label: 'Siding', type: 'option', sortable: false, filterable: true, options: $sidingFilterOptions),
            new Column(id: 'expected_loading_date', label: 'Expected loading', type: 'date', sortable: true, filterable: true),
            new Column(id: 'state', label: 'State', type: 'text', sortable: true, filterable: false),
            new Column(id: 'e_demand_reference_id', label: 'e-Demand ref', type: 'text', sortable: false, filterable: false),
        ];
    }

    public static function tableBaseQuery(): Builder
    {
        $user = request()->user();
        $sidingIds = $user && $user->isSuperAdmin()
            ? Siding::query()->pluck('id')->all()
            : ($user ? $user->sidings()->get()->pluck('id')->all() : []);

        // Backward compatibility: some legacy users only have `users.siding_id`
        // and no rows in the `user_siding` pivot table.
        if ($user && ! $user->isSuperAdmin() && $sidingIds === [] && $user->siding_id !== null) {
            $sidingIds = [(int) $user->siding_id];
        }

        $query = Indent::query()
            ->with([
                'siding:id,name,code',
                'rake:id,indent_id,rake_number',
                'rake.rakeWeighments' => fn ($q) => $q->select(['id', 'rake_id']),
            ])
            ->whereIn('siding_id', $sidingIds);

        /** @var array<string, mixed> $filters */
        $filters = request()->query('filter', []);
        $hasExplicitIndentDateFilter = array_key_exists('indent_date', $filters);

        if (! $hasExplicitIndentDateFilter) {
            $monthStart = CarbonImmutable::now()->startOfMonth()->toDateString();
            $monthEnd = CarbonImmutable::now()->endOfMonth()->toDateString();

            $query->whereBetween('indent_date', [$monthStart, $monthEnd]);
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
            AllowedFilter::custom('indent_date', new OperatorFilter('date')),
            AllowedFilter::custom('expected_loading_date', new OperatorFilter('date')),
            AllowedFilter::custom('fnr_number', new OperatorFilter('text')),
            AllowedFilter::callback('siding', static function (Builder $query, mixed $value): void {
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

                $query->whereHas('siding', static function (Builder $sidingQuery) use ($operator, $values): void {
                    if ($operator === 'in') {
                        $sidingQuery->whereIn('id', $values);

                        return;
                    }

                    if ($operator === 'not_in') {
                        $sidingQuery->whereNotIn('id', $values);

                        return;
                    }

                    $needle = $values[0];

                    if ($operator === 'eq') {
                        $sidingQuery->where(function (Builder $q) use ($needle): void {
                            $q->where('code', $needle)->orWhere('name', $needle);
                        });

                        return;
                    }

                    // contains
                    $sidingQuery->where(function (Builder $q) use ($needle): void {
                        $q->where('code', 'LIKE', '%'.$needle.'%')
                            ->orWhere('name', 'LIKE', '%'.$needle.'%');
                    });
                });
            }),
        ];
    }

    public static function tableAllowedSorts(): array
    {
        return [
            AllowedSort::callback('rake_number', static function (Builder $query, bool $descending, string $_property): void {
                $direction = $descending ? 'desc' : 'asc';
                $query->orderBy(
                    Rake::query()
                        ->select('rake_number')
                        ->whereColumn('rakes.indent_id', 'indents.id')
                        ->limit(1),
                    $direction,
                );
            }),
            'indent_number',
            'indent_date',
            'expected_loading_date',
            'fnr_number',
            'state',
        ];
    }
}
