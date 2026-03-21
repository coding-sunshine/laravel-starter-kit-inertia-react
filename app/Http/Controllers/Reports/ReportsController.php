<?php

declare(strict_types=1);

namespace App\Http\Controllers\Reports;

use App\Actions\RunReportAction;
use App\Exports\ReportArrayExport;
use App\Http\Controllers\Controller;
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

        return Inertia::render('reports/index', [
            'reports' => $reports,
            'sidings' => $sidings,
        ]);
    }

    public function generate(Request $request): JsonResponse|StreamedResponse|BinaryFileResponse
    {
        $validated = $request->validate([
            'key' => ['required', 'string', 'in:'.implode(',', RunReportAction::RAKE_MANAGEMENT_REPORT_KEYS)],
            'siding_id' => ['nullable', 'integer', 'exists:sidings,id'],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
            'preview' => ['nullable', 'boolean'],
            'preview_limit' => ['nullable', 'integer', 'min:1', 'max:200'],
            'export_xlsx' => ['nullable', 'boolean'],
        ]);

        $user = $request->user();
        $sidingIds = $user->isSuperAdmin()
            ? Siding::query()->pluck('id')->all()
            : $user->accessibleSidings()->get()->pluck('id')->all();

        if ($sidingIds === []) {
            return response()->json(['data' => []]);
        }

        $params = array_filter([
            'siding_id' => $validated['siding_id'] ?? null,
            'date_from' => $validated['date_from'] ?? null,
            'date_to' => $validated['date_to'] ?? null,
        ]);

        $preview = $request->boolean('preview');
        $previewLimit = (int) ($validated['preview_limit'] ?? 25);
        $exportXlsx = $request->boolean('export_xlsx');

        if ($preview) {
            $params['limit'] = $previewLimit > 0 ? $previewLimit : 25;
        }

        if ($exportXlsx || $request->boolean('export_csv')) {
            // Exports should return full dataset.
            $params['no_limit'] = true;
        }

        $data = resolve(RunReportAction::class)->handle($validated['key'], $sidingIds, $params);

        if ($request->boolean('export_csv')) {
            return $this->exportCsv($validated['key'], $data);
        }

        if ($exportXlsx) {
            $name = RunReportAction::REPORT_KEYS[$validated['key']]['name'] ?? $validated['key'];
            $filename = str_replace(' ', '_', $name).'_'.date('Y-m-d').'.xlsx';

            return Excel::download(new ReportArrayExport($data), $filename);
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
