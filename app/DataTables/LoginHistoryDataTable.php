<?php

declare(strict_types=1);

namespace App\DataTables;

use App\Models\LoginEvent;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Machour\DataTable\AbstractDataTable;
use Machour\DataTable\Columns\ColumnBuilder;
use Machour\DataTable\Concerns\HasExport;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
final class LoginHistoryDataTable extends AbstractDataTable
{
    use HasExport;

    protected static ?int $defaultPerPage = 50;

    public function __construct(
        public int $id,
        public ?string $user_name,
        public ?string $ip_address,
        public ?string $device_fingerprint,
        public ?string $user_agent,
        public ?string $created_at,
    ) {}

    public static function fromModel(LoginEvent $model): self
    {
        return new self(
            id: $model->id,
            user_name: $model->user?->name,
            ip_address: $model->ip_address,
            device_fingerprint: $model->device_fingerprint ? mb_substr($model->device_fingerprint, 0, 8).'...' : null,
            user_agent: $model->user_agent,
            created_at: $model->created_at?->format('Y-m-d H:i:s'),
        );
    }

    public static function tableColumns(): array
    {
        return [
            ColumnBuilder::make('id', 'ID')->sortable()->build(),
            ColumnBuilder::make('user_name', 'User')->build(),
            ColumnBuilder::make('ip_address', 'IP Address')->build(),
            ColumnBuilder::make('device_fingerprint', 'Device (masked)')->build(),
            ColumnBuilder::make('user_agent', 'User Agent')->build(),
            ColumnBuilder::make('created_at', 'Login At')->sortable()->build(),
        ];
    }

    public static function tableAllowedFilters(): array
    {
        return [
            AllowedFilter::partial('ip_address'),
        ];
    }

    public static function inertiaProps(Request $request): array
    {
        return [
            'tableData' => self::makeTable($request)->toArray(),
            'searchableColumns' => self::tableSearchableColumns(),
        ];
    }

    public static function tableBaseQuery(): Builder
    {
        return LoginEvent::query()->with('user');
    }

    public static function tableDefaultSort(): string
    {
        return '-created_at';
    }

    public static function tableAuthorize(string $action, Request $request): bool
    {
        return $request->user()?->hasRole('super-admin') ?? false;
    }

    public static function tableExportName(): string
    {
        return 'login-history';
    }
}
