<?php

declare(strict_types=1);

namespace App\Support\Dashboard;

use App\Models\Siding;
use Carbon\CarbonInterface;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

final class DashboardFilterResolver
{
    /**
     * @return array{
     *   allSidingIds: array<int>,
     *   filteredSidingIds: array<int>,
     *   period: string,
     *   from: CarbonInterface,
     *   to: CarbonInterface,
     *   powerPlant: string|null,
     *   rakeNumber: string|null,
     *   loaderId: int|null,
     *   shift: string|null,
     *   penaltyTypeId: int|null,
     *   dailyRakeDate: CarbonInterface,
     *   coalTransportDate: CarbonInterface,
     *   section: string,
     *   filterContext: array<string, mixed>,
     * }
     */
    public function resolve(Request $request): array
    {
        $user = $request->user();
        $period = (string) $request->input('period', 'yesterday');

        if ($period !== 'custom') {
            $request->merge(['from' => null, 'to' => null]);
        }

        $allSidingIds = $user->isSuperAdmin()
            ? Siding::query()->pluck('id')->all()
            : $user->accessibleSidings()->get()->pluck('id')->all();

        [$from, $to] = $this->resolveDateRange($request);

        $parsedSidingIds = $this->parseRequestedSidingIds($request);

        $filteredSidingIds = $parsedSidingIds !== []
            ? array_values(array_intersect($allSidingIds, $parsedSidingIds))
            : $allSidingIds;

        $powerPlant = $request->filled('power_plant') ? (string) $request->input('power_plant') : null;
        $rakeNumber = $request->filled('rake_number') ? (string) $request->input('rake_number') : null;
        $loaderId = $request->integer('loader_id') ?: null;
        $shift = $request->filled('shift') ? (string) $request->input('shift') : null;
        $penaltyTypeId = $request->integer('penalty_type') ?: null;

        $dailyRakeDate = $this->parseSingleDate($request, 'daily_rake_date', now()->subDay()->startOfDay());
        $coalTransportDate = $this->parseSingleDate($request, 'coal_transport_date', now()->subDay()->startOfDay());

        $allowedSections = ['executive-overview', 'operations', 'penalty-control', 'siding-performance', 'rake-performance', 'loader-overload', 'power-plant'];
        $section = (string) $request->input('section', 'executive-overview');
        $section = in_array($section, $allowedSections, true) ? $section : 'executive-overview';

        $filterContext = [
            'period' => $period,
            'power_plant' => $powerPlant,
            'rake_number' => $rakeNumber,
            'loader_id' => $loaderId,
            'shift' => $shift,
            'penalty_type_id' => $penaltyTypeId,
        ];

        return [
            'allSidingIds' => $allSidingIds,
            'filteredSidingIds' => $filteredSidingIds,
            'period' => $period,
            'from' => $from,
            'to' => $to,
            'powerPlant' => $powerPlant,
            'rakeNumber' => $rakeNumber,
            'loaderId' => $loaderId,
            'shift' => $shift,
            'penaltyTypeId' => $penaltyTypeId,
            'dailyRakeDate' => $dailyRakeDate,
            'coalTransportDate' => $coalTransportDate,
            'section' => $section,
            'filterContext' => $filterContext,
        ];
    }

    /**
     * Siding scope for mobile admin KPIs (today-only dashboard).
     *
     * - Superadmin: all sidings; optional `siding_ids` / `siding_id` narrows the selection.
     * - Other users: if `users.siding_id` is set, only that siding (request siding filter still intersects).
     * - Otherwise: same as dashboard (`accessibleSidings` / `siding_user` pivot).
     *
     * @return array{allSidingIds: array<int>, filteredSidingIds: array<int>}
     */
    public function resolveAdminKpiSidings(Request $request): array
    {
        $user = $request->user();

        if ($user->isSuperAdmin()) {
            $allSidingIds = Siding::query()->pluck('id')->all();
        } elseif ($user->siding_id !== null && (int) $user->siding_id > 0) {
            $sid = (int) $user->siding_id;
            $allSidingIds = Siding::query()->whereKey($sid)->exists() ? [$sid] : [];
        } else {
            $allSidingIds = $user->accessibleSidings()->get()->pluck('id')->all();
        }

        $parsedSidingIds = $this->parseRequestedSidingIds($request);

        $filteredSidingIds = $parsedSidingIds !== []
            ? array_values(array_intersect($allSidingIds, $parsedSidingIds))
            : $allSidingIds;

        return [
            'allSidingIds' => $allSidingIds,
            'filteredSidingIds' => $filteredSidingIds,
        ];
    }

    /**
     * Re-expose the SQL helper so API can share logic if needed.
     */
    public function dateOnlyBetweenSql(string $column, bool $columnIsPostgresDate = false): string
    {
        $driver = DB::getDriverName();

        if ($driver === 'pgsql') {
            if ($columnIsPostgresDate) {
                return "({$column})::date BETWEEN ? AND ?";
            }

            $tz = config('app.timezone', 'UTC');
            $tzEscaped = str_replace("'", "''", $tz);

            return "(({$column} AT TIME ZONE 'UTC' AT TIME ZONE '{$tzEscaped}')::date) BETWEEN ? AND ?";
        }

        return "DATE({$column}) BETWEEN ? AND ?";
    }

    /**
     * @return list<int>
     */
    private function parseRequestedSidingIds(Request $request): array
    {
        $rawSidingIds = $request->has('siding_ids')
            ? $request->input('siding_ids')
            : ($request->has('siding_id') ? $request->input('siding_id') : null);

        if ($rawSidingIds === null) {
            return [];
        }

        if (is_string($rawSidingIds)) {
            return array_values(array_filter(
                array_map('intval', preg_split('/\s*,\s*/', $rawSidingIds) ?: []),
                static fn (int $v): bool => $v > 0,
            ));
        }

        return array_values(array_filter(
            array_map('intval', (array) $rawSidingIds),
            static fn (int $v): bool => $v > 0,
        ));
    }

    /**
     * @return array{0: CarbonInterface, 1: CarbonInterface}
     */
    private function resolveDateRange(Request $request): array
    {
        $period = (string) $request->input('period', 'yesterday');
        $tz = config('app.timezone', 'UTC');
        $now = now($tz);

        if ($period === 'custom') {
            $from = $this->parseRequestDate($request, 'from', $tz) ?? $now->copy()->startOfMonth();
            $to = $now->copy()->endOfDay();
            $parsedTo = $this->parseRequestDate($request, 'to', $tz);
            if ($parsedTo !== null) {
                $to = $parsedTo->copy()->endOfDay();
            }

            return [$from, $to];
        }

        return match ($period) {
            'yesterday' => [
                $now->copy()->subDay()->startOfDay(),
                $now->copy()->subDay()->endOfDay(),
            ],
            'today' => [
                $now->copy()->startOfDay(),
                $now->copy()->endOfDay(),
            ],
            'week' => [
                $now->copy()->startOfWeek(),
                $now->copy()->endOfDay(),
            ],
            'last_week' => [
                $now->copy()->subWeek()->startOfWeek(),
                $now->copy()->subWeek()->endOfWeek(),
            ],
            'month' => [
                $now->copy()->startOfMonth(),
                $now->copy()->endOfDay(),
            ],
            'last_month' => [
                $now->copy()->subMonthNoOverflow()->startOfMonth(),
                $now->copy()->subMonthNoOverflow()->endOfMonth(),
            ],
            default => [
                $now->copy()->subDay()->startOfDay(),
                $now->copy()->subDay()->endOfDay(),
            ],
        };
    }

    private function parseRequestDate(Request $request, string $key, string $tz): ?\Carbon\Carbon
    {
        $value = $request->query($key) ?? $request->input($key);
        if ($value === null || $value === '') {
            return null;
        }

        if ($value instanceof CarbonInterface) {
            return \Carbon\Carbon::parse($value->format('Y-m-d'), $tz)->startOfDay();
        }

        return \Carbon\Carbon::parse((string) $value, $tz)->startOfDay();
    }

    private function parseSingleDate(Request $request, string $key, CarbonInterface $default): CarbonInterface
    {
        $tz = config('app.timezone', 'UTC');
        $parsed = $this->parseRequestDate($request, $key, $tz);

        return $parsed ?? $default;
    }
}
