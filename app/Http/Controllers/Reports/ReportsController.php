<?php

declare(strict_types=1);

namespace App\Http\Controllers\Reports;

use App\Actions\RunReportAction;
use App\Http\Controllers\Controller;
use App\Models\Siding;
use App\Services\SidingContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class ReportsController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();
        $sidingIds = SidingContext::activeSidingIds($user);
        $sidings = Siding::query()
            ->whereIn('id', $sidingIds)
            ->orderBy('name')
            ->get(['id', 'name', 'code']);

        return Inertia::render('reports/index', [
            'reports' => RunReportAction::REPORT_KEYS,
            'sidings' => $sidings,
        ]);
    }

    public function generate(Request $request): JsonResponse|StreamedResponse
    {
        $validated = $request->validate([
            'key' => ['required', 'string', 'in:'.implode(',', array_keys(RunReportAction::REPORT_KEYS))],
            'siding_id' => ['nullable', 'integer', 'exists:sidings,id'],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
        ]);

        $user = $request->user();
        $sidingIds = SidingContext::activeSidingIds($user);

        if ($sidingIds === []) {
            return response()->json(['data' => []]);
        }

        $params = array_filter([
            'siding_id' => $validated['siding_id'] ?? null,
            'date_from' => $validated['date_from'] ?? null,
            'date_to' => $validated['date_to'] ?? null,
        ]);

        $data = app(RunReportAction::class)->handle($validated['key'], $sidingIds, $params);

        if ($request->boolean('export_csv')) {
            return $this->exportCsv($validated['key'], $data);
        }

        return response()->json(['data' => $data]);
    }

    /**
     * @param  array<int, array<string, mixed>>  $data
     */
    private function exportCsv(string $key, array $data): StreamedResponse
    {
        $name = RunReportAction::REPORT_KEYS[$key]['name'] ?? $key;
        $filename = str_replace(' ', '_', $name).'_'.date('Y-m-d').'.csv';

        return response()->streamDownload(function () use ($data) {
            $out = fopen('php://output', 'w');
            if ($data !== []) {
                fputcsv($out, array_keys((array) $data[0]));
                foreach ($data as $row) {
                    fputcsv($out, (array) $row);
                }
            }
            fclose($out);
        }, $filename, [
            'Content-Type' => 'text/csv',
        ]);
    }
}
