<?php

declare(strict_types=1);

namespace App\Http\Controllers\Reports;

use App\Actions\RunReportAction;
use App\Exports\ReportArrayExport;
use App\Http\Controllers\Controller;
use App\Models\Loader;
use App\Models\LoaderOperator;
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
        foreach (RunReportAction::COAL_LOGESTIC_ADVANCE_REPORT_KEYS as $key) {
            if (isset(RunReportAction::REPORT_KEYS[$key])) {
                $reports[$key] = RunReportAction::REPORT_KEYS[$key];
            }
        }

        $powerPlants = PowerPlant::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'code']);

        $loaders = Loader::query()
            ->whereIn('siding_id', $sidingIds)
            ->where('is_active', true)
            ->orderBy('loader_name')
            ->get(['id', 'code', 'loader_name', 'siding_id']);

        $loaderOperators = LoaderOperator::query()
            ->where('is_active', true)
            ->where(function ($q) use ($sidingIds): void {
                $q->whereIn('siding_id', $sidingIds)
                    ->orWhereNull('siding_id');
            })
            ->orderBy('name')
            ->get(['id', 'name', 'siding_id']);

        return Inertia::render('reports/index', [
            'reports' => $reports,
            'sidings' => $sidings,
            'powerPlants' => $powerPlants,
            'reportLoaders' => $loaders,
            'reportLoaderOperators' => $loaderOperators,
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
            'underload_threshold_percent' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'loader_id' => ['nullable', 'integer'],
            'loader_operator_id' => ['nullable', 'integer'],
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

        if (array_key_exists('underload_threshold_percent', $validated) && $validated['underload_threshold_percent'] !== null) {
            $params['underload_threshold_percent'] = (float) $validated['underload_threshold_percent'];
        }

        if (! empty($validated['loader_id'])) {
            $loaderId = (int) $validated['loader_id'];
            $loaderOk = Loader::query()
                ->whereKey($loaderId)
                ->whereNull('deleted_at')
                ->whereIn('siding_id', $sidingIds)
                ->exists();

            abort_unless($loaderOk, 422, 'Loader is invalid or inaccessible for your sidings.');
            $params['loader_id'] = $loaderId;
        }

        if (! empty($validated['loader_operator_id'])) {
            $operatorId = (int) $validated['loader_operator_id'];
            $operator = LoaderOperator::query()
                ->whereKey($operatorId)
                ->where('is_active', true)
                ->first();

            if ($operator === null) {
                abort(422, 'Loader operator not found.');
            }

            abort_unless(
                $operator->siding_id === null || in_array($operator->siding_id, $sidingIds, true),
                422,
                'Loader operator is not available for your sidings.'
            );

            $params['loader_operator_name'] = mb_trim($operator->name);
        }

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
