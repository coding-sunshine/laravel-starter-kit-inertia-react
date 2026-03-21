<?php

declare(strict_types=1);

namespace App\Http\Controllers\Weighments;

use App\Http\Controllers\Controller;
use App\Models\Rake;
use App\Models\Siding;
use App\Models\Weighment;
use App\Services\RakeWeighmentPdfImporter;
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

    public function store(
        Request $request,
        WeighmentPdfImporter $historicalImporter,
        RakeWeighmentPdfImporter $rakeImporter,
    ): RedirectResponse {
        $validated = $request->validate([
            'pdf' => ['required', 'file', 'mimes:pdf', 'max:20480'],
            'rake_id' => ['nullable', 'integer', 'exists:rakes,id'],
        ]);

        $user = $request->user();
        $userId = (int) $user->id;
        $pdf = $validated['pdf'];
        $rakeId = $validated['rake_id'] ?? null;

        if ($rakeId !== null) {
            $rake = Rake::query()->findOrFail($rakeId);

            $sidingIds = $user->isSuperAdmin()
                ? Siding::query()->pluck('id')->all()
                : $user->accessibleSidings()->get()->pluck('id')->all();

            if (! in_array($rake->siding_id, $sidingIds, true)) {
                return back()
                    ->withErrors(['pdf' => 'You are not allowed to attach weighments to the selected rake.'])
                    ->withInput();
            }

            try {
                Log::info('WeighmentsController: received PDF upload for rake import', [
                    'user_id' => $userId,
                    'rake_id' => $rake->id,
                    'original_name' => $pdf->getClientOriginalName(),
                    'size' => $pdf->getSize(),
                ]);

                $rakeImporter->importForRake($rake, $pdf, $userId);
            } catch (InvalidArgumentException $e) {
                Log::warning('WeighmentsController: rake import failed with validation error', [
                    'user_id' => $userId,
                    'rake_id' => $rake->id,
                    'message' => $e->getMessage(),
                ]);

                return back()
                    ->withErrors(['pdf' => $e->getMessage()])
                    ->withInput();
            } catch (Throwable $e) {
                Log::error('WeighmentsController: rake import failed with unexpected error', [
                    'user_id' => $userId,
                    'rake_id' => $rake->id,
                    'exception' => get_class($e),
                    'message' => $e->getMessage(),
                ]);

                return back()
                    ->withErrors(['pdf' => 'Weighment import failed due to an unexpected error. Please check logs.'])
                    ->withInput();
            }

            return to_route('rakes.show', $rake)
                ->with('success', 'Weighment recorded.');
        }

        try {
            Log::info('WeighmentsController: received PDF upload for import', [
                'user_id' => $userId,
                'original_name' => $pdf->getClientOriginalName(),
                'size' => $pdf->getSize(),
            ]);

            $weighment = $historicalImporter->import(
                $pdf,
                $userId
            );
        } catch (InvalidArgumentException $e) {
            Log::warning('WeighmentsController: import failed with validation error', [
                'user_id' => $userId,
                'message' => $e->getMessage(),
            ]);

            return back()
                ->withErrors(['pdf' => $e->getMessage()])
                ->withInput();
        } catch (Throwable $e) {
            Log::error('WeighmentsController: import failed with unexpected error', [
                'user_id' => $userId,
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
