<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\RecalculateDailyVehicleEntryRollups;
use App\Models\DailyVehicleEntry;
use App\Models\DailyVehicleEntryRollup;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Super-admin-only UI: day-wise rollup summaries for road-dispatch daily vehicle entries, bucket detail for one day, and single-day rebuilds.
 *
 * Not linked from the sidebar; no route permission mapping — authorization is {@see User::isSuperAdmin()} only.
 */
final class DailyVehicleEntryRollupAdminController extends Controller
{
    private const int DAYS_PER_PAGE = 50;

    private const int MAX_RANGE_DAYS = 366;

    public function index(Request $request): Response
    {
        $user = Auth::user();
        abort_unless($user instanceof User && $user->isSuperAdmin(), 403);

        $validated = $request->validate([
            'date_from' => ['sometimes', 'nullable', 'date'],
            'date_to' => ['sometimes', 'nullable', 'date'],
            'detail_date' => ['sometimes', 'nullable', 'date'],
        ]);

        $tz = config('app.timezone', 'UTC');
        $today = CarbonImmutable::now($tz)->startOfDay();

        $fromInput = $validated['date_from'] ?? null;
        $toInput = $validated['date_to'] ?? null;

        $fromDay = is_string($fromInput) && $fromInput !== ''
            ? CarbonImmutable::parse($fromInput, $tz)->startOfDay()
            : $today->subDays(13);
        $toDay = is_string($toInput) && $toInput !== ''
            ? CarbonImmutable::parse($toInput, $tz)->startOfDay()
            : $today;

        if ($fromDay->gt($toDay)) {
            [$fromDay, $toDay] = [$toDay, $fromDay];
        }

        if ($fromDay->diffInDays($toDay) > self::MAX_RANGE_DAYS) {
            throw ValidationException::withMessages([
                'date_to' => __('Date range cannot exceed :days days.', ['days' => self::MAX_RANGE_DAYS]),
            ]);
        }

        $dateFromStr = $fromDay->toDateString();
        $dateToStr = $toDay->toDateString();

        $detailInput = $validated['detail_date'] ?? null;
        $detailDateStr = null;
        if (is_string($detailInput) && $detailInput !== '') {
            $detailParsed = CarbonImmutable::parse($detailInput, $tz)->startOfDay()->toDateString();
            if ($detailParsed >= $dateFromStr && $detailParsed <= $dateToStr) {
                $detailDateStr = $detailParsed;
            }
        }

        $calendarDatesDescending = [];
        for ($d = $toDay->copy(); $d->gte($fromDay); $d = $d->subDay()) {
            $calendarDatesDescending[] = $d->toDateString();
        }

        $totalDays = count($calendarDatesDescending);
        $page = max(1, (int) $request->query('page', 1));
        $slice = array_slice($calendarDatesDescending, ($page - 1) * self::DAYS_PER_PAGE, self::DAYS_PER_PAGE);

        $dayRows = [];
        foreach ($slice as $dateStr) {
            $agg = DailyVehicleEntryRollup::query()
                ->whereDate('rollup_day', $dateStr)
                ->selectRaw(
                    'COUNT(*) as bucket_count, '
                    .'COALESCE(SUM(entries_count), 0) as total_entries, '
                    .'COALESCE(SUM(completed_net_wt_mt), 0) as total_completed_net_mt, '
                    .'COALESCE(SUM(pending_gross_wt_mt), 0) as total_pending_gross_mt'
                )
                ->first();

            $dayRows[] = [
                'date' => $dateStr,
                'bucket_count' => (int) ($agg->bucket_count ?? 0),
                'total_entries' => (int) ($agg->total_entries ?? 0),
                'total_completed_net_mt' => (string) ($agg->total_completed_net_mt ?? '0'),
                'total_pending_gross_mt' => (string) ($agg->total_pending_gross_mt ?? '0'),
                'has_entry_source' => DailyVehicleEntry::query()
                    ->where('entry_type', DailyVehicleEntry::ENTRY_TYPE_ROAD_DISPATCH)
                    ->whereDate('entry_date', $dateStr)
                    ->exists(),
            ];
        }

        $daysPaginator = new LengthAwarePaginator(
            $dayRows,
            $totalDays,
            self::DAYS_PER_PAGE,
            $page,
            [
                'path' => $request->url(),
                'pageName' => 'page',
            ]
        );
        $daysPaginator->withQueryString();

        $detailRollups = null;
        if ($detailDateStr !== null) {
            $detailRollups = DailyVehicleEntryRollup::query()
                ->with(['siding:id,name,code'])
                ->whereDate('rollup_day', $detailDateStr)
                ->orderByDesc('updated_at')
                ->orderByDesc('id')
                ->paginate(self::DAYS_PER_PAGE, ['*'], 'detail_page')
                ->withQueryString();
        }

        return Inertia::render('DailyVehicleEntryRollups/Index', [
            'days' => $daysPaginator,
            'detailRollups' => $detailRollups,
            'filters' => [
                'date_from' => $dateFromStr,
                'date_to' => $dateToStr,
                'detail_date' => $detailDateStr,
            ],
            'flash' => [
                'success' => $request->session()->pull('success'),
            ],
        ]);
    }

    public function recalculate(Request $request, RecalculateDailyVehicleEntryRollups $rollup): RedirectResponse
    {
        $user = Auth::user();
        abort_unless($user instanceof User && $user->isSuperAdmin(), 403);

        $validated = $request->validate([
            'date' => ['required', 'date'],
            'date_from' => ['sometimes', 'nullable', 'date'],
            'date_to' => ['sometimes', 'nullable', 'date'],
            'detail_date' => ['sometimes', 'nullable', 'date'],
        ]);

        $tz = config('app.timezone', 'UTC');
        $dateString = CarbonImmutable::parse((string) $validated['date'], $tz)->toDateString();

        $hasSource = DailyVehicleEntry::query()
            ->where('entry_type', DailyVehicleEntry::ENTRY_TYPE_ROAD_DISPATCH)
            ->whereDate('entry_date', $dateString)
            ->exists();

        if (! $hasSource) {
            throw ValidationException::withMessages([
                'date' => __('No road dispatch daily vehicle entries exist for this date.'),
            ]);
        }

        $inserted = $rollup->handle($dateString, $dateString);

        $redirectQuery = array_filter([
            'date_from' => $validated['date_from'] ?? null,
            'date_to' => $validated['date_to'] ?? null,
            'detail_date' => $validated['detail_date'] ?? null,
        ], fn (?string $v): bool => is_string($v) && $v !== '');

        return redirect()->route('daily-vehicle-entry-rollups.index', $redirectQuery)
            ->with('success', sprintf('Recalculated %d rollup row(s) for %s.', $inserted, $dateString));
    }
}
