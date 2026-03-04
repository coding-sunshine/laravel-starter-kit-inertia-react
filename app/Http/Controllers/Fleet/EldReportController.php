<?php

declare(strict_types=1);

namespace App\Http\Controllers\Fleet;

use App\Http\Controllers\Controller;
use App\Models\Fleet\Driver;
use App\Models\Fleet\DriverWorkingTime;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final class EldReportController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', DriverWorkingTime::class);

        $dateFrom = $request->input('date_from', now()->subDays(7)->format('Y-m-d'));
        $dateTo = $request->input('date_to', now()->format('Y-m-d'));
        $driverId = $request->input('driver_id');

        $query = DriverWorkingTime::query()
            ->with('driver:id,first_name,last_name')
            ->whereBetween('date', [$dateFrom, $dateTo])
            ->orderBy('date')
            ->orderBy('driver_id');

        if ($driverId) {
            $query->where('driver_id', $driverId);
        }

        $rows = $query->get()->map(fn ($row): array => [
            'id' => $row->id,
            'driver_id' => $row->driver_id,
            'driver_name' => $row->driver ? $row->driver->first_name.' '.$row->driver->last_name : null,
            'date' => $row->date->format('Y-m-d'),
            'shift_start_time' => $row->shift_start_time?->format('H:i'),
            'shift_end_time' => $row->shift_end_time?->format('H:i'),
            'driving_time_minutes' => $row->driving_time_minutes,
            'break_time_minutes' => $row->break_time_minutes,
            'other_work_time_minutes' => $row->other_work_time_minutes,
            'rest_time_minutes' => $row->rest_time_minutes ?? 0,
            'total_duty_time_minutes' => $row->total_duty_time_minutes,
            'wtd_compliant' => $row->wtd_compliant,
            'rtd_compliant' => $row->rtd_compliant,
            'manual_entry' => $row->manual_entry,
        ]);

        $drivers = Driver::query()
            ->orderBy('last_name')
            ->get(['id', 'first_name', 'last_name'])
            ->map(fn ($d): array => ['id' => $d->id, 'name' => $d->first_name.' '.$d->last_name]);

        return Inertia::render('Fleet/Reports/EldReport', [
            'rows' => $rows,
            'drivers' => $drivers,
            'filters' => [
                'date_from' => $dateFrom,
                'date_to' => $dateTo,
                'driver_id' => $driverId,
            ],
        ]);
    }
}
