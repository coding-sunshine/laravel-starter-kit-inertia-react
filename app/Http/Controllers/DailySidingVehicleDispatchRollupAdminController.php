<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\RecalculateDailySidingVehicleDispatchRollups;
use App\Models\DailySidingVehicleDispatchRollup;
use App\Models\User;
use App\Models\VehicleDispatch;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Super-admin-only UI: day-wise rollup summaries in a date range, bucket detail for one day, and single-day rebuilds.
 *
 * Not linked from the sidebar; no route permission mapping — authorization is {@see User::isSuperAdmin()} only.
 */
final class DailySidingVehicleDispatchRollupAdminController extends Controller
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
            $agg = DailySidingVehicleDispatchRollup::query()
                ->whereDate('issued_on_date', $dateStr)
                ->selectRaw('COUNT(*) as bucket_count, COALESCE(SUM(dispatches_count), 0) as total_dispatches, COALESCE(SUM(qty_mineral_mt), 0) as total_qty')
                ->first();

            $dayRows[] = [
                'date' => $dateStr,
                'bucket_count' => (int) ($agg->bucket_count ?? 0),
                'total_dispatches' => (int) ($agg->total_dispatches ?? 0),
                'total_qty_mineral_mt' => (string) ($agg->total_qty ?? '0'),
                'has_dispatch_source' => VehicleDispatch::query()
                    ->whereNotNull('issued_on')
                    ->whereDate('issued_on', $dateStr)
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
            $detailRollups = DailySidingVehicleDispatchRollup::query()
                ->with(['siding:id,name,code'])
                ->whereDate('issued_on_date', $detailDateStr)
                ->orderByDesc('updated_at')
                ->orderByDesc('id')
                ->paginate(self::DAYS_PER_PAGE, ['*'], 'detail_page')
                ->withQueryString();
        }

        return Inertia::render('DailySidingDispatchRollups/Index', [
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

    public function recalculate(Request $request, RecalculateDailySidingVehicleDispatchRollups $rollup): RedirectResponse
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

        $hasSource = VehicleDispatch::query()
            ->whereNotNull('issued_on')
            ->whereDate('issued_on', $dateString)
            ->exists();

        if (! $hasSource) {
            throw ValidationException::withMessages([
                'date' => __('No dispatch records have been uploaded for this date.'),
            ]);
        }

        $inserted = $rollup->handle($dateString, $dateString);

        $redirectQuery = array_filter([
            'date_from' => $validated['date_from'] ?? null,
            'date_to' => $validated['date_to'] ?? null,
            'detail_date' => $validated['detail_date'] ?? null,
        ], fn (?string $v): bool => is_string($v) && $v !== '');

        return redirect()->route('daily-siding-dispatch-rollups.index', $redirectQuery)
            ->with('success', sprintf('Recalculated %d rollup row(s) for %s.', $inserted, $dateString));
    }
}
