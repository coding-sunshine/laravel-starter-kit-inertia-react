<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\HistoricalMine;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;
use Throwable;

final class HistoricalMineController extends Controller
{
    public function index(Request $request): InertiaResponse
    {
        $query = HistoricalMine::query()->orderByDesc('id');

        $mines = $query
            ->paginate(250)
            ->withQueryString()
            ->through(function (HistoricalMine $mine): array {
                return [
                    'id' => $mine->id,
                    'month' => $mine->month?->format('Y-m'),
                    'trips_dispatched' => $mine->trips_dispatched,
                    'dispatched_qty' => $mine->dispatched_qty,
                    'trips_received' => $mine->trips_received,
                    'received_qty' => $mine->received_qty,
                    'coal_production_qty' => $mine->coal_production_qty,
                    'ob_production_qty' => $mine->ob_production_qty,
                ];
            });

        return Inertia::render('historical/mines/index', [
            'mines' => $mines,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'month' => ['nullable', 'string'],
            'trips_dispatched' => 'nullable|integer',
            'dispatched_qty' => 'nullable|numeric',
            'trips_received' => 'nullable|integer',
            'received_qty' => 'nullable|numeric',
            'coal_production_qty' => 'nullable|numeric',
            'ob_production_qty' => 'nullable|numeric',
        ]);

        if (array_key_exists('month', $data)) {
            $data['month'] = $this->parseMonth($data['month']);
        }

        $mine = HistoricalMine::query()->create($data);

        return response()->json([
            'mine' => [
                'id' => $mine->id,
                'month' => $mine->month?->format('Y-m'),
                'trips_dispatched' => $mine->trips_dispatched,
                'dispatched_qty' => $mine->dispatched_qty,
                'trips_received' => $mine->trips_received,
                'received_qty' => $mine->received_qty,
                'coal_production_qty' => $mine->coal_production_qty,
                'ob_production_qty' => $mine->ob_production_qty,
            ],
        ], 201);
    }

    public function update(Request $request, HistoricalMine $mine): JsonResponse
    {
        $data = $request->validate([
            'month' => ['nullable', 'string'],
            'trips_dispatched' => 'nullable|integer',
            'dispatched_qty' => 'nullable|numeric',
            'trips_received' => 'nullable|integer',
            'received_qty' => 'nullable|numeric',
            'coal_production_qty' => 'nullable|numeric',
            'ob_production_qty' => 'nullable|numeric',
        ]);

        if (array_key_exists('month', $data)) {
            $data['month'] = $this->parseMonth($data['month']);
        }

        $mine->fill($data);
        $mine->save();

        return response()->json([
            'mine' => [
                'id' => $mine->id,
                'month' => $mine->month?->format('Y-m'),
                'trips_dispatched' => $mine->trips_dispatched,
                'dispatched_qty' => $mine->dispatched_qty,
                'trips_received' => $mine->trips_received,
                'received_qty' => $mine->received_qty,
                'coal_production_qty' => $mine->coal_production_qty,
                'ob_production_qty' => $mine->ob_production_qty,
            ],
        ]);
    }

    public function destroy(Request $request, HistoricalMine $mine): JsonResponse
    {
        $id = $mine->id;
        $mine->delete();

        return response()->json([
            'deleted' => true,
            'id' => $id,
        ]);
    }

    private function parseMonth(?string $value): ?Carbon
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $value) === 1) {
            return Carbon::createFromFormat('Y-m-d', $value.'-01')->startOfDay();
        }

        try {
            return Carbon::parse($value)->startOfDay();
        } catch (Throwable) {
            return null;
        }
    }
}
