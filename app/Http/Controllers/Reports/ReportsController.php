<?php

declare(strict_types=1);

namespace App\Http\Controllers\Reports;

use App\Actions\RunReportAction;
use App\Exports\ReportArrayExport;
use App\Http\Controllers\Controller;
use App\Models\PowerPlant;
use App\Models\Siding;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class ReportsController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();
        $sidingIds = $user->isSuperAdmin()
            ? Siding::query()->pluck('id')->all()
            : $user->accessibleSidings()->get()->pluck('id')->all();
        $sidings = Siding::query()
            ->whereIn('id', $sidingIds)
            ->orderBy('name')
            ->get(['id', 'name', 'code']);

        $reports = [];
        foreach (RunReportAction::RAKE_MANAGEMENT_REPORT_KEYS as $key) {
            if (isset(RunReportAction::REPORT_KEYS[$key])) {
                $reports[$key] = RunReportAction::REPORT_KEYS[$key];
            }
        }
        foreach (RunReportAction::COAL_LOGESTIC_CORE_REPORT_KEYS as $key) {
            if (isset(RunReportAction::REPORT_KEYS[$key])) {
                $reports[$key] = RunReportAction::REPORT_KEYS[$key];
            }
        }

        $powerPlants = PowerPlant::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'code']);

        return Inertia::render('reports/index', [
            'reports' => $reports,
            'sidings' => $sidings,
            'powerPlants' => $powerPlants,
        ]);
    }

    public function generate(Request $request): JsonResponse|StreamedResponse|BinaryFileResponse
    {
        $validated = $request->validate([
            'key' => ['required', 'string', 'in:'.implode(',', RunReportAction::reportGenerateKeys())],
            'siding_id' => ['nullable', 'integer', 'exists:sidings,id'],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
            'rake_number' => ['nullable', 'string', 'max:255'],
            'loader' => ['nullable', 'string', 'max:255'],
            'power_plant_id' => ['nullable', 'integer', 'exists:power_plants,id'],
            'penalty_stage' => ['nullable', 'string', 'in:pre_rr,post_rr'],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:60'],
            'export_xlsx' => ['nullable', 'boolean'],
        ]);

        $user = $request->user();
        $sidingIds = $user->isSuperAdmin()
            ? Siding::query()->pluck('id')->all()
            : $user->accessibleSidings()->get()->pluck('id')->all();

        $page = max(1, (int) ($validated['page'] ?? 1));
        $perPage = max(1, min(60, (int) ($validated['per_page'] ?? 60)));
        $emptyGridResponse = fn (): JsonResponse => response()->json([
            'data' => [],
            'meta' => [
                'current_page' => 1,
                'per_page' => $perPage,
                'total' => 0,
                'last_page' => 1,
            ],
        ]);

        if ($sidingIds === []) {
            return $emptyGridResponse();
        }

        $params = array_filter([
            'siding_id' => $validated['siding_id'] ?? null,
            'date_from' => $validated['date_from'] ?? null,
            'date_to' => $validated['date_to'] ?? null,
            'rake_number' => $validated['rake_number'] ?? null,
            'loader' => $validated['loader'] ?? null,
            'power_plant_id' => $validated['power_plant_id'] ?? null,
            'penalty_stage' => $validated['penalty_stage'] ?? null,
        ]);

        $exportXlsx = $request->boolean('export_xlsx');

        if ($exportXlsx || $request->boolean('export_csv')) {
            $params['no_limit'] = true;
        }

        if ($request->boolean('export_csv')) {
            $data = resolve(RunReportAction::class)->handle($validated['key'], $sidingIds, $params);

            return $this->exportCsv($validated['key'], $data);
        }

        if ($exportXlsx) {
            $data = resolve(RunReportAction::class)->handle($validated['key'], $sidingIds, $params);
            $name = RunReportAction::REPORT_KEYS[$validated['key']]['name'] ?? $validated['key'];
            $filename = str_replace(' ', '_', $name).'_'.date('Y-m-d').'.xlsx';

            return Excel::download(new ReportArrayExport($data), $filename);
        }

        $payload = resolve(RunReportAction::class)->handlePaginated($validated['key'], $sidingIds, $params, $page, $perPage);

        return response()->json($payload);
    }

    /**
     * @param  array<int, array<string, mixed>>  $data
     */
    private function exportCsv(string $key, array $data): StreamedResponse
    {
        $name = RunReportAction::REPORT_KEYS[$key]['name'] ?? $key;
        $filename = str_replace(' ', '_', $name).'_'.date('Y-m-d').'.csv';

        return response()->streamDownload(function () use ($data): void {
            $out = fopen('php://output', 'w');
            if ($data !== []) {
                fputcsv($out, array_keys($data[0]), escape: '\\');
                foreach ($data as $row) {
                    fputcsv($out, (array) $row, escape: '\\');
                }
            }
            fclose($out);
        }, $filename, [
            'Content-Type' => 'text/csv',
        ]);
    }
}
