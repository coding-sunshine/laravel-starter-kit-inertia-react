<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\ImportInventory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response as HttpResponse;
use Inertia\Inertia;
use Inertia\Response;

final class InventoryApiController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('inventory/index', [
            'recent_imports' => [],
            'supported_formats' => ['json', 'csv'],
        ]);
    }

    public function import(Request $request): JsonResponse
    {
        $request->validate([
            'file' => ['required', 'file', 'mimes:json,csv,txt', 'max:10240'],
        ]);

        $file = $request->file('file');
        $extension = mb_strtolower($file->getClientOriginalExtension());
        $contents = file_get_contents($file->getRealPath());

        $data = match ($extension) {
            'json' => json_decode($contents, true) ?? [],
            'csv', 'txt' => $this->parseCsv($contents),
            default => [],
        };

        $results = app(ImportInventory::class)->handle($data, $extension);

        return response()->json([
            'success' => true,
            'results' => $results,
        ]);
    }

    public function template(string $type): HttpResponse
    {
        $headers = match ($type) {
            'lots' => ['id', 'name', 'price', 'area', 'status', 'project_id', 'lot_number', 'street_address'],
            'projects' => ['id', 'name', 'description', 'status', 'suburb', 'state', 'postcode', 'developer'],
            default => abort(404),
        };

        $csv = implode(',', $headers)."\n";

        return response($csv, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$type}-template.csv\"",
        ]);
    }

    private function parseCsv(string $contents): array
    {
        $lines = explode("\n", mb_trim($contents));

        if (count($lines) < 2) {
            return [];
        }

        $headers = str_getcsv(array_shift($lines));
        $rows = [];

        foreach ($lines as $line) {
            if (mb_trim($line) === '') {
                continue;
            }

            $values = str_getcsv($line);
            $rows[] = array_combine($headers, array_pad($values, count($headers), null));
        }

        return $rows;
    }
}
