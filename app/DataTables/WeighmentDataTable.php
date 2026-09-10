<?php

declare(strict_types=1);

namespace App\DataTables;

use App\Models\Rake;
use App\Models\Siding;
use App\Models\User;
use App\Models\Weighment;
use Illuminate\Database\Eloquent\Builder;
use Machour\DataTable\AbstractDataTable;
use Machour\DataTable\Columns\Column;
use Machour\DataTable\Filters\OperatorFilter;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\AllowedSort;

final class WeighmentDataTable extends AbstractDataTable
{
    public function __construct(
        public int $id,
        public int $rake_id,
        public ?string $rake_number,
        public ?string $train_name,
        public ?string $from_station,
        public ?string $to_station,
        public ?string $priority_number,
        public ?string $created_at,
    ) {}

    public static function fromModel(Weighment $model): self
    {
        return new self(
            id: $model->id,
            rake_id: $model->rake_id,
            rake_number: $model->rake?->rake_number,
            train_name: $model->train_name,
            from_station: $model->from_station,
            to_station: $model->to_station,
            priority_number: $model->priority_number,
            created_at: $model->created_at?->toIso8601String(),
        );
    }

    public static function tableColumns(): array
    {
        return [
            new Column(id: 'rake_number', label: 'Rake #', type: 'text', sortable: true, filterable: true),
            new Column(
                id: 'siding_code',
                label: 'Siding',
                type: 'option',
                sortable: false,
                filterable: true,
                visible: false,
                options: self::filterableSidingOptions(),
            ),
            new Column(id: 'train_name', label: 'Train name', type: 'text', sortable: true, filterable: true),
            new Column(id: 'from_station', label: 'From station', type: 'text', sortable: true, filterable: false),
            new Column(id: 'to_station', label: 'To station', type: 'text', sortable: true, filterable: false),
            new Column(id: 'priority_number', label: 'Priority number', type: 'text', sortable: true, filterable: false),
            new Column(id: 'created_at', label: 'Created at', type: 'date', sortable: true, filterable: false),
        ];
    }

    public static function tableBaseQuery(): Builder
    {
        $query = Weighment::query()->with(['rake:id,rake_number,siding_id']);

        $user = request()->user();
        $sidingIds = $user instanceof User && $user->isSuperAdmin()
            ? Siding::query()->pluck('id')->all()
            : ($user ? $user->sidings()->get()->pluck('id')->all() : []);

        if ($user instanceof User && ! $user->isSuperAdmin() && $sidingIds === [] && $user->siding_id !== null) {
            $sidingIds = [(int) $user->siding_id];
        }

        if ($sidingIds === []) {
            $query->whereRaw('0 = 1');
        } else {
            $query->whereHas('rake', fn (Builder $q): Builder => $q->whereIn('siding_id', $sidingIds));
        }

        return $query;
    }

    public static function tableDefaultSort(): string
    {
        return '-created_at';
    }

    public static function tableAllowedFilters(): array
    {
        return [
            AllowedFilter::callback('rake_number', function (Builder $query, mixed $value): void {
                self::applyRakeNumberFilter($query, $value);
            }),
            AllowedFilter::callback('siding_code', function (Builder $query, mixed $value): void {
                self::applyRakeSidingFilter($query, $value);
            }),
            AllowedFilter::custom('train_name', new OperatorFilter('text')),
        ];
    }

    public static function tableAllowedSorts(): array
    {
        $weighmentsTable = (new Weighment)->getTable();
        $rakesTable = (new Rake)->getTable();

        return [
            AllowedSort::callback('rake_number', static function (Builder $query, bool $descending, string $_property) use ($weighmentsTable, $rakesTable): void {
                $direction = $descending ? 'desc' : 'asc';
                $query->leftJoin($rakesTable, "{$rakesTable}.id", '=', "{$weighmentsTable}.rake_id")
                    ->select("{$weighmentsTable}.*")
                    ->orderBy("{$rakesTable}.rake_number", $direction);
            }),
            'train_name',
            'from_station',
            'to_station',
            'priority_number',
            'created_at',
        ];
    }

    private static function applyRakeNumberFilter(Builder $query, mixed $value): void
    {
        $raw = is_array($value) ? implode(',', $value) : (string) $value;
        $operator = 'contains';
        $rawValue = $raw;

        if (preg_match('/^([a-z_]+):(.+)$/i', $raw, $matches)) {
            $known = ['eq', 'neq', 'contains', 'in', 'not_in'];
            if (in_array($matches[1], $known, true)) {
                $operator = $matches[1];
                $rawValue = $matches[2];
            }
        }

        $values = array_values(array_filter(explode(',', $rawValue), static fn (string $v): bool => $v !== ''));
        if ($values === []) {
            return;
        }

        $query->whereHas('rake', static function (Builder $rakeQuery) use ($operator, $values): void {
            match ($operator) {
                'eq' => $rakeQuery->where('rake_number', $values[0]),
                'neq' => $rakeQuery->where('rake_number', '!=', $values[0]),
                'in' => $rakeQuery->whereIn('rake_number', $values),
                'not_in' => $rakeQuery->whereNotIn('rake_number', $values),
                default => $rakeQuery->where('rake_number', 'LIKE', '%'.$values[0].'%'),
            };
        });
    }

    private static function applyRakeSidingFilter(Builder $query, mixed $value): void
    {
        $query->whereHas('rake', static function (Builder $rakeQuery) use ($value): void {
            self::applySidingIdFilterOnRakeQuery($rakeQuery, $value);
        });
    }

    private static function applySidingIdFilterOnRakeQuery(Builder $query, mixed $value): void
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

    /**
     * @return list<array{label: string, value: string}>
     */
    private static function filterableSidingOptions(): array
    {
        $user = request()->user();
        $sidingIds = $user instanceof User && $user->isSuperAdmin()
            ? Siding::query()->orderBy('name')->pluck('id')->all()
            : ($user ? $user->sidings()->orderBy('name')->get()->pluck('id')->all() : []);

        if ($user instanceof User && ! $user->isSuperAdmin() && $sidingIds === [] && $user->siding_id !== null) {
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
}
