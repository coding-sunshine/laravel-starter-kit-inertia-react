<?php

declare(strict_types=1);

namespace App\Http\Controllers\RR;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreRrUploadRequest;
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
            $parsed = $this->parser->parse($request->file('pdf'));
            $rrDocument = $this->rrImportService->import($parsed, $request);

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
