<?php

declare(strict_types=1);

namespace App\Support\Dashboard;

use App\Models\Siding;
use Carbon\Carbon;
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
     *   loaderOperatorName: string|null,
     *   shift: string|null,
     *   penaltyTypeId: int|null,
     *   rakePenaltyScope: string,
     *   underloadThresholdPercent: float,
     *   dailyRakeDate: CarbonInterface,
     *   coalTransportDate: CarbonInterface,
     *   section: string,
     *   filterContext: array<string, mixed>,
     * }
     */
    public function resolve(Request $request): array
    {
        $user = $request->user();
        $period = (string) $request->input('period', 'month');

        if ($period !== 'custom') {
            $request->merge(['from' => null, 'to' => null]);
        }

        $allSidingIds = $user->isSuperAdmin()
            ? Siding::query()->pluck('id')->all()
            : $user->accessibleSidings()->get()->pluck('id')->all();

        [$from, $to] = $this->resolveDateRangeFromRequest($request);

        $parsedSidingIds = $this->parseRequestedSidingIds($request);

        $filteredSidingIds = $parsedSidingIds !== []
            ? array_values(array_intersect($allSidingIds, $parsedSidingIds))
            : $allSidingIds;

        $powerPlant = $request->filled('power_plant') ? (string) $request->input('power_plant') : null;
        $rakeNumber = $request->filled('rake_number') ? (string) $request->input('rake_number') : null;
        $loaderId = $request->integer('loader_id') ?: null;
        $loaderOperatorName = null;
        if ($request->filled('loader_operator')) {
            $t = mb_trim((string) $request->input('loader_operator'));
            $loaderOperatorName = $t !== '' ? $t : null;
        }
        $shift = $request->filled('shift') ? (string) $request->input('shift') : null;
        $penaltyTypeId = $request->integer('penalty_type') ?: null;
        $rakePenaltyScope = $this->resolveRakePenaltyScope($request);

        $underloadThresholdPercent = 1.0;
        if ($request->filled('underload_threshold')) {
            $raw = $request->input('underload_threshold');
            $parsed = is_numeric($raw) ? (float) $raw : null;
            if ($parsed !== null && ! is_nan($parsed)) {
                $underloadThresholdPercent = max(0.0, min(100.0, $parsed));
            }
        }

        $dailyRakeDate = $this->parseSingleDate($request, 'daily_rake_date', now()->subDay()->startOfDay());
        $coalTransportDate = $this->parseSingleDate($request, 'coal_transport_date', now()->subDay()->startOfDay());

        $allowedSections = DashboardWidgetPermissions::orderedDashboardSectionIds();
        $permittedSections = DashboardWidgetPermissions::permittedDashboardSectionIdsForUser($user);
        $fallbackSection = $permittedSections[0] ?? 'executive-overview';

        $querySection = $request->query('section');
        if (is_string($querySection)
            && in_array($querySection, $allowedSections, true)
            && in_array($querySection, $permittedSections, true)) {
            $section = $querySection;
        } else {
            $section = $fallbackSection;
        }

        $filterContext = [
            'period' => $period,
            'power_plant' => $powerPlant,
            'rake_number' => $rakeNumber,
            'loader_id' => $loaderId,
            'loader_operator_name' => $loaderOperatorName,
            'shift' => $shift,
            'penalty_type_id' => $penaltyTypeId,
            'rake_penalty_scope' => $rakePenaltyScope,
            'underload_threshold_percent' => $underloadThresholdPercent,
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
            'loaderOperatorName' => $loaderOperatorName,
            'shift' => $shift,
            'penaltyTypeId' => $penaltyTypeId,
            'rakePenaltyScope' => $rakePenaltyScope,
            'underloadThresholdPercent' => $underloadThresholdPercent,
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
     * Inclusive date bounds for a dashboard period preset (or custom Y-m-d strings).
     *
     * @return array{0: Carbon, 1: Carbon}
     */
    public function boundsForPeriod(
        string $period,
        ?string $fromYmd = null,
        ?string $toYmd = null,
        ?CarbonInterface $now = null,
    ): array {
        $tz = config('app.timezone', 'UTC');
        $nowCarbon = $now !== null
            ? Carbon::parse($now->toDateTimeString(), $tz)
            : now($tz);

        if ($period === 'custom') {
            $from = ($fromYmd !== null && $fromYmd !== '')
                ? Carbon::parse($fromYmd, $tz)->startOfDay()
                : $nowCarbon->copy()->startOfMonth();
            $to = ($toYmd !== null && $toYmd !== '')
                ? Carbon::parse($toYmd, $tz)->copy()->endOfDay()
                : $nowCarbon->copy()->endOfDay();

            return [$from, $to];
        }

        return match ($period) {
            'yesterday' => [
                $nowCarbon->copy()->subDay()->startOfDay(),
                $nowCarbon->copy()->subDay()->endOfDay(),
            ],
            'today' => [
                $nowCarbon->copy()->startOfDay(),
                $nowCarbon->copy()->endOfDay(),
            ],
            'week' => [
                $nowCarbon->copy()->startOfWeek(),
                $nowCarbon->copy()->endOfDay(),
            ],
            'last_week' => [
                $nowCarbon->copy()->subWeek()->startOfWeek(),
                $nowCarbon->copy()->subWeek()->endOfWeek(),
            ],
            'month' => [
                $nowCarbon->copy()->startOfMonth(),
                $nowCarbon->copy()->endOfDay(),
            ],
            'last_month' => [
                $nowCarbon->copy()->subMonthNoOverflow()->startOfMonth(),
                $nowCarbon->copy()->subMonthNoOverflow()->endOfMonth(),
            ],
            default => [
                $nowCarbon->copy()->subDay()->startOfDay(),
                $nowCarbon->copy()->subDay()->endOfDay(),
            ],
        };
    }

    /**
     * Inclusive date bounds for the dispatch summary card (independent of global dashboard period).
     *
     * @return array{0: Carbon, 1: Carbon}
     */
    public function boundsForDispatchSummaryPeriod(
        string $period,
        ?CarbonInterface $now = null,
    ): array {
        $tz = config('app.timezone', 'UTC');
        $nowCarbon = $now !== null
            ? Carbon::parse($now->toDateTimeString(), $tz)
            : now($tz);

        if (in_array($period, ['today', 'yesterday', 'month', 'last_month'], true)) {
            return $this->boundsForPeriod($period, null, null, $nowCarbon);
        }

        if ($period === 'fy') {
            $fyStart = $nowCarbon->month >= 4
                ? $nowCarbon->copy()->startOfDay()->setDate($nowCarbon->year, 4, 1)
                : $nowCarbon->copy()->startOfDay()->setDate($nowCarbon->year - 1, 4, 1);

            return [
                $fyStart,
                $fyStart->copy()->addYear()->subDay()->endOfDay(),
            ];
        }

        return $this->boundsForPeriod('today', null, null, $nowCarbon);
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
    private function resolveDateRangeFromRequest(Request $request): array
    {
        $period = (string) $request->input('period', 'month');
        $tz = config('app.timezone', 'UTC');
        $now = now($tz);

        if ($period === 'custom') {
            $fromParsed = $this->parseRequestDate($request, 'from', $tz);
            $toParsed = $this->parseRequestDate($request, 'to', $tz);

            return $this->boundsForPeriod(
                'custom',
                $fromParsed?->toDateString(),
                $toParsed?->toDateString(),
                $now,
            );
        }

        return $this->boundsForPeriod($period, null, null, $now);
    }

    private function parseRequestDate(Request $request, string $key, string $tz): ?Carbon
    {
        $value = $request->query($key) ?? $request->input($key);
        if ($value === null || $value === '') {
            return null;
        }

        if ($value instanceof CarbonInterface) {
            return Carbon::parse($value->format('Y-m-d'), $tz)->startOfDay();
        }

        return Carbon::parse((string) $value, $tz)->startOfDay();
    }

    private function parseSingleDate(Request $request, string $key, CarbonInterface $default): CarbonInterface
    {
        $tz = config('app.timezone', 'UTC');
        $parsed = $this->parseRequestDate($request, $key, $tz);

        return $parsed ?? $default;
    }

    private function resolveRakePenaltyScope(Request $request): string
    {
        $rawScope = $request->input('rake_penalty_scope');
        if (! is_string($rawScope)) {
            return 'all';
        }

        $scope = mb_trim($rawScope);

        return in_array($scope, ['all', 'with_penalties'], true) ? $scope : 'all';
    }
}
