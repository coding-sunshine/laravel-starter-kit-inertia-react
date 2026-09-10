<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\HistoricalMine;
use App\Models\Siding;
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
        $request->merge([
            'date_from' => $request->filled('date_from') ? $request->input('date_from') : null,
            'date_to' => $request->filled('date_to') ? $request->input('date_to') : null,
            'siding_id' => $request->filled('siding_id') ? $request->input('siding_id') : null,
        ]);

        $validated = $request->validate([
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date'],
            'siding_id' => ['nullable', 'integer', 'exists:sidings,id'],
        ]);

        $query = HistoricalMine::query()
            ->with('siding:id,name');

        if (! empty($validated['date_from'])) {
            $query->whereDate('month', '>=', $validated['date_from']);
        }

        if (! empty($validated['date_to'])) {
            $query->whereDate('month', '<=', $validated['date_to']);
        }

        if (! empty($validated['siding_id'])) {
            $query->where('siding_id', $validated['siding_id']);
        }

        $query->orderByDesc('month')->orderByDesc('id');

        $mines = $query
            ->paginate(250)
            ->withQueryString()
            ->through(function (HistoricalMine $mine): array {
                return [
                    'id' => $mine->id,
                    'month' => $mine->month?->format('Y-m-d'),
                    'siding_id' => $mine->siding_id,
                    'siding_name' => $mine->siding?->name,
                    'trips_dispatched' => $mine->trips_dispatched,
                    'dispatched_qty' => $mine->dispatched_qty,
                    'trips_received' => $mine->trips_received,
                    'received_qty' => $mine->received_qty,
                    'coal_production_qty' => $mine->coal_production_qty,
                    'ob_production_qty' => $mine->ob_production_qty,
                    'remarks' => $mine->remarks,
                ];
            });

        $sidings = Siding::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(fn (Siding $s): array => ['id' => $s->id, 'name' => $s->name])
            ->values()
            ->all();

        return Inertia::render('historical/mines/index', [
            'mines' => $mines,
            'sidings' => $sidings,
            'filters' => [
                'date_from' => $validated['date_from'] ?? null,
                'date_to' => $validated['date_to'] ?? null,
                'siding_id' => isset($validated['siding_id']) ? (int) $validated['siding_id'] : null,
            ],
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'month' => ['nullable', 'string'],
            'siding_id' => ['nullable', 'integer', 'exists:sidings,id'],
            'trips_dispatched' => 'nullable|integer',
            'dispatched_qty' => 'nullable|numeric',
            'trips_received' => 'nullable|integer',
            'received_qty' => 'nullable|numeric',
            'coal_production_qty' => 'nullable|numeric',
            'ob_production_qty' => 'nullable|numeric',
            'remarks' => 'nullable|string|max:65535',
        ]);

        if (array_key_exists('month', $data)) {
            $data['month'] = $this->parseMonth($data['month']);
        }

        $mine = HistoricalMine::query()->create($data);
        $mine->load('siding:id,name');

        return response()->json([
            'mine' => [
                'id' => $mine->id,
                'month' => $mine->month?->format('Y-m-d'),
                'siding_id' => $mine->siding_id,
                'siding_name' => $mine->siding?->name,
                'trips_dispatched' => $mine->trips_dispatched,
                'dispatched_qty' => $mine->dispatched_qty,
                'trips_received' => $mine->trips_received,
                'received_qty' => $mine->received_qty,
                'coal_production_qty' => $mine->coal_production_qty,
                'ob_production_qty' => $mine->ob_production_qty,
                'remarks' => $mine->remarks,
            ],
        ], 201);
    }

    public function update(Request $request, HistoricalMine $mine): JsonResponse
    {
        $data = $request->validate([
            'month' => ['nullable', 'string'],
            'siding_id' => ['nullable', 'integer', 'exists:sidings,id'],
            'trips_dispatched' => 'nullable|integer',
            'dispatched_qty' => 'nullable|numeric',
            'trips_received' => 'nullable|integer',
            'received_qty' => 'nullable|numeric',
            'coal_production_qty' => 'nullable|numeric',
            'ob_production_qty' => 'nullable|numeric',
            'remarks' => 'nullable|string|max:65535',
        ]);

        if (array_key_exists('month', $data)) {
            $data['month'] = $this->parseMonth($data['month']);
        }

        $mine->fill($data);
        $mine->save();
        $mine->load('siding:id,name');

        return response()->json([
            'mine' => [
                'id' => $mine->id,
                'month' => $mine->month?->format('Y-m-d'),
                'siding_id' => $mine->siding_id,
                'siding_name' => $mine->siding?->name,
                'trips_dispatched' => $mine->trips_dispatched,
                'dispatched_qty' => $mine->dispatched_qty,
                'trips_received' => $mine->trips_received,
                'received_qty' => $mine->received_qty,
                'coal_production_qty' => $mine->coal_production_qty,
                'ob_production_qty' => $mine->ob_production_qty,
                'remarks' => $mine->remarks,
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
