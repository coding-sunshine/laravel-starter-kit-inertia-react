<?php

declare(strict_types=1);

namespace App\Http\Controllers\Rakes;

use App\Http\Controllers\Controller;
use App\Models\Rake;
use App\Services\RakeWeighmentPdfImporter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use InvalidArgumentException;
use Throwable;

final class RakeWeighmentController extends Controller
{
    public function store(Request $request, Rake $rake, RakeWeighmentPdfImporter $importer): RedirectResponse
    {
        // $this->authorize('update', $rake);

        $validated = $request->validate([
            'weighment_pdf' => ['required', 'file', 'mimes:pdf', 'max:20480'],
        ]);

        try {
            $importer->importForRake($rake, $validated['weighment_pdf'], (int) $request->user()->id);
        } catch (InvalidArgumentException $e) {
            Log::warning('RakeWeighmentController: import validation failed', [
                'rake_id' => $rake->id,
                'message' => $e->getMessage(),
            ]);

            return back()
                ->withErrors(['weighment_pdf' => $e->getMessage()])
                ->withInput();
        } catch (Throwable $e) {
            Log::error('RakeWeighmentController: import failed', [
                'rake_id' => $rake->id,
                'exception' => get_class($e),
                'message' => $e->getMessage(),
            ]);

            return back()
                ->withErrors(['weighment_pdf' => 'Weighment import failed due to an unexpected error. Please check logs.'])
                ->withInput();
        }

        // After successful upload, stay on the rake page so the user sees
        // the updated weighment data in the Rake workflow itself.
        return to_route('rakes.show', $rake)
            ->with('success', 'Weighment recorded.');
    }

    public function destroy(Rake $rake): RedirectResponse
    {
        // $this->authorize('update', $rake);

        $weighments = $rake->rakeWeighments()->get();

        foreach ($weighments as $weighment) {
            if ($weighment->pdf_file_path) {
                Storage::disk('public')->delete($weighment->pdf_file_path);
            }

            $weighment->delete();
        }

        return to_route('rakes.show', $rake)
            ->with('success', 'Rake weighment data deleted.');
    }
}
