<?php

declare(strict_types=1);

namespace App\Http\Controllers\RR;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreRrUploadRequest;
use App\Models\DiverrtDestination;
use App\Models\Rake;
use App\Services\Railway\RrImportService;
use App\Services\Railway\RrParserService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;
use Throwable;

final class RrUploadController extends Controller
{
    public function __construct(
        private readonly RrParserService $parser,
        private readonly RrImportService $rrImportService,
    ) {}

    public function store(StoreRrUploadRequest $request): RedirectResponse|JsonResponse
    {
        try {
            $validated = $request->validated();
            $parsed = $this->parser->parse($request->file('pdf'));

            $rake = null;
            if (isset($validated['rake_id'])) {
                $rake = Rake::query()->find((int) $validated['rake_id']);
                if ($rake === null) {
                    throw new InvalidArgumentException('Selected rake is invalid or no longer available.');
                }
            }

            $diverrtDestination = null;
            if (! empty($validated['diverrt_destination_id'])) {
                $diverrtDestination = DiverrtDestination::query()->find((int) $validated['diverrt_destination_id']);
            }

            $rrDocument = $this->rrImportService->importSnapshotOnly($parsed, $request, $validated, $rake, $diverrtDestination);

            if ($rake !== null) {
                return redirect()->route('rakes.show', $rake)
                    ->with('success', 'Railway Receipt uploaded and parsed successfully.')
                    ->with('rr_document_id', $rrDocument->id);
            }

            return redirect()->route('railway-receipts.show', $rrDocument)
                ->with('success', 'Railway Receipt uploaded and parsed successfully.');
        } catch (InvalidArgumentException $e) {
            Log::warning('RR upload validation failed', ['error' => $e->getMessage()]);
            if ($request->wantsJson()) {
                return response()->json(['message' => $e->getMessage()], 422);
            }

            return back()->withErrors(['pdf' => $e->getMessage()]);
        } catch (Throwable $e) {
            Log::error('RR upload failed', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            if ($request->wantsJson()) {
                return response()->json([
                    'message' => 'Failed to process Railway Receipt. '.$e->getMessage(),
                ], 500);
            }

            return back()->withErrors(['pdf' => 'Failed to process Railway Receipt. Please ensure the PDF is valid and try again.']);
        }
    }
}
