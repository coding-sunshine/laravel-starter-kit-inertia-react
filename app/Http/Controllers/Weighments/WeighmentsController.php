<?php

declare(strict_types=1);

namespace App\Http\Controllers\Weighments;

use App\Actions\DeleteStandaloneHistoricalWeighmentAction;
use App\Http\Controllers\Controller;
use App\Models\Rake;
use App\Models\Siding;
use App\Models\User;
use App\Models\Weighment;
use App\Services\RakeWeighmentPdfImporter;
use App\Services\WeighmentPdfImporter;
use App\Support\RrmcsDeletionRules;
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
        /** @var User $user */
        $user = $request->user();

        $sidingIds = $user->isSuperAdmin()
            ? Siding::query()->pluck('id')->all()
            : $user->accessibleSidings()->get()->pluck('id')->all();

        $query = Weighment::query()
            ->with('rake')
            ->orderByDesc('created_at');

        if ($sidingIds === []) {
            $query->whereRaw('0 = 1');
        } else {
            $query->whereHas('rake', fn ($q) => $q->whereIn('siding_id', $sidingIds));
        }

        $weighments = $query->limit(100)->get();

        return Inertia::render('weighments/index', [
            'weighments' => $weighments,
        ]);
    }

    public function show(Request $request, Weighment $weighment): Response
    {
        /** @var User $user */
        $user = $request->user();

        $weighment->load([
            'rake.siding',
            'rake.wagons',
            'rakeWagonWeighments.wagon',
        ]);

        $this->assertUserCanAccessWeighmentRakeSiding($user, $weighment);

        $canDeleteWeighment = RrmcsDeletionRules::isWeighmentDeletableFromStandaloneModule($weighment)
            && (
                $user->can('bypass-permissions')
                || $user->hasPermissionTo('sections.weighments.delete')
            );

        return Inertia::render('weighments/show', [
            'weighment' => $weighment,
            'can_delete_weighment' => $canDeleteWeighment,
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

        /** @var User $user */
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

        $sidingIds = $user->isSuperAdmin()
            ? Siding::query()->pluck('id')->all()
            : $user->accessibleSidings()->get()->pluck('id')->all();

        if ($sidingIds === []) {
            return back()
                ->withErrors(['pdf' => 'You have no assigned sidings for weighment import.'])
                ->withInput();
        }

        try {
            Log::info('WeighmentsController: received PDF upload for import', [
                'user_id' => $userId,
                'original_name' => $pdf->getClientOriginalName(),
                'size' => $pdf->getSize(),
            ]);

            $weighment = $historicalImporter->import(
                $pdf,
                $userId,
                $sidingIds
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

    public function destroy(Request $request, Weighment $weighment, DeleteStandaloneHistoricalWeighmentAction $action): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();

        $weighment->loadMissing('rake');
        $this->assertUserCanAccessWeighmentRakeSiding($user, $weighment);

        try {
            $action->handle($weighment);
        } catch (InvalidArgumentException $e) {
            return back()->withErrors(['delete' => $e->getMessage()]);
        }

        return to_route('weighments.index')
            ->with('success', 'Historical weighment and related rake data removed.');
    }

    private function assertUserCanAccessWeighmentRakeSiding(User $user, Weighment $weighment): void
    {
        $rake = $weighment->rake;
        abort_if($rake === null, 404);

        abort_unless($user->canAccessSiding((int) $rake->siding_id), 403);
    }
}
