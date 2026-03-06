<?php

declare(strict_types=1);

namespace App\Http\Controllers\Weighments;

use App\Http\Controllers\Controller;
use App\Models\Weighment;
use App\Services\WeighmentPdfImporter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;
use InvalidArgumentException;
use Throwable;

final class WeighmentsController extends Controller
{
    public function index(Request $request): Response
    {
        $weighments = Weighment::query()
            ->with('rake')
            ->orderByDesc('created_at')
            ->limit(100)
            ->get();

        return Inertia::render('weighments/index', [
            'weighments' => $weighments,
        ]);
    }

    public function show(Weighment $weighment): Response
    {
        $weighment->load([
            'rake.siding',
            'rake.wagons',
            'rakeWagonWeighments.wagon',
        ]);

        return Inertia::render('weighments/show', [
            'weighment' => $weighment,
        ]);
    }

    public function store(Request $request, WeighmentPdfImporter $importer): RedirectResponse
    {
        $validated = $request->validate([
            'pdf' => ['required', 'file', 'mimes:pdf', 'max:20480'],
        ]);

        try {
            Log::info('WeighmentsController: received PDF upload for import', [
                'user_id' => $request->user()->id ?? null,
                'original_name' => $validated['pdf']->getClientOriginalName(),
                'size' => $validated['pdf']->getSize(),
            ]);

            $weighment = $importer->import(
                $validated['pdf'],
                (int) $request->user()->id
            );
        } catch (InvalidArgumentException $e) {
            Log::warning('WeighmentsController: import failed with validation error', [
                'user_id' => $request->user()->id ?? null,
                'message' => $e->getMessage(),
            ]);

            return back()
                ->withErrors(['pdf' => $e->getMessage()])
                ->withInput();
        } catch (Throwable $e) {
            Log::error('WeighmentsController: import failed with unexpected error', [
                'user_id' => $request->user()->id ?? null,
                'exception' => get_class($e),
                'message' => $e->getMessage(),
            ]);

            return back()
                ->withErrors(['pdf' => 'Weighment import failed due to an unexpected error. Please check logs.'])
                ->withInput();
        }

        return to_route('weighments.show', $weighment)
            ->with('success', 'Weighment PDF imported successfully.');
    }
}
