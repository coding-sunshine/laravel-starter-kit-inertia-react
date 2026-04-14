<?php

declare(strict_types=1);

namespace App\Http\Controllers\Weighments;

use App\Actions\DeleteStandaloneHistoricalWeighmentAction;
use App\Actions\RecordManualRakeWeighment;
use App\DataTables\WeighmentsRakeDataTable;
use App\Http\Controllers\Controller;
use App\Models\Rake;
use App\Models\Siding;
use App\Models\User;
use App\Models\Weighment;
use App\Services\RakeWeighmentExcelTemplateResolver;
use App\Services\RakeWeighmentPdfImporter;
use App\Services\WeighmentPdfImporter;
use App\Support\RrmcsDeletionRules;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;
use InvalidArgumentException;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Throwable;

final class WeighmentsController extends Controller
{
    public function index(Request $request): Response
    {
        $this->applyWeighmentsHubDefaultLoadingDateFilter($request);

        return Inertia::render('weighments/index', [
            'tableData' => WeighmentsRakeDataTable::makeTable($request),
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
            && $this->userMatchesWeighmentsHubDeletePermissions($user);

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
            'pdf' => ['required', 'file', 'mimes:pdf,xlsx,xls', 'max:20480'],
            'rake_id' => ['nullable', 'integer', 'exists:rakes,id'],
        ]);

        /** @var User $user */
        $user = $request->user();
        $userId = (int) $user->id;
        $pdf = $validated['pdf'];
        $rakeId = $validated['rake_id'] ?? null;
        $extension = mb_strtolower((string) $pdf->getClientOriginalExtension());
        $isXlsx = in_array($extension, ['xlsx', 'xls'], true);

        if ($rakeId !== null) {
            $rake = Rake::query()->findOrFail($rakeId);

            $sidingIds = $user->isSuperAdmin()
                ? Siding::query()->pluck('id')->all()
                : $user->sidings()->get()->pluck('id')->all();

            // Backward compatibility: some legacy users only have `users.siding_id`
            // and no rows in the `user_siding` pivot table.
            if (! $user->isSuperAdmin() && $sidingIds === [] && $user->siding_id !== null) {
                $sidingIds = [(int) $user->siding_id];
            }

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

                $weighment = $isXlsx
                    ? $rakeImporter->importForRakeFromXlsx($rake, $pdf, $userId)
                    : $rakeImporter->importForRake($rake, $pdf, $userId);
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

            return to_route('weighments.show', $weighment->getKey())
                ->with('success', 'Weighment recorded.');
        }

        if ($isXlsx) {
            return back()
                ->withErrors(['pdf' => 'Excel upload requires selecting a rake.'])
                ->withInput();
        }

        $sidingIds = $user->isSuperAdmin()
            ? Siding::query()->pluck('id')->all()
            : $user->sidings()->get()->pluck('id')->all();

        // Backward compatibility: some legacy users only have `users.siding_id`
        // and no rows in the `user_siding` pivot table.
        if (! $user->isSuperAdmin() && $sidingIds === [] && $user->siding_id !== null) {
            $sidingIds = [(int) $user->siding_id];
        }

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

    public function storeManual(Request $request, RecordManualRakeWeighment $recordManual): RedirectResponse
    {
        $validated = $request->validate([
            'rake_id' => ['required', 'integer', 'exists:rakes,id'],
            'total_net_weight_mt' => ['required', 'numeric', 'min:0.01', 'max:99999999.99'],
            'from_station' => ['nullable', 'string', 'max:255'],
            'to_station' => ['nullable', 'string', 'max:255'],
            'priority_number' => ['nullable', 'string', 'max:255'],
        ]);

        /** @var User $user */
        $user = $request->user();
        $rake = Rake::query()->findOrFail((int) $validated['rake_id']);

        $sidingIds = $user->isSuperAdmin()
            ? Siding::query()->pluck('id')->all()
            : $user->sidings()->get()->pluck('id')->all();

        if (! $user->isSuperAdmin() && $sidingIds === [] && $user->siding_id !== null) {
            $sidingIds = [(int) $user->siding_id];
        }

        if (! in_array($rake->siding_id, $sidingIds, true)) {
            return back()
                ->withErrors(['rake_id' => 'You are not allowed to record weighments for the selected rake.'])
                ->withInput();
        }

        $emptyToNull = static function (mixed $v): ?string {
            if ($v === null || $v === '') {
                return null;
            }
            $t = mb_trim((string) $v);

            return $t === '' ? null : $t;
        };

        $payload = [
            'total_net_weight_mt' => (float) $validated['total_net_weight_mt'],
            'from_station' => $emptyToNull($validated['from_station'] ?? null),
            'to_station' => $emptyToNull($validated['to_station'] ?? null),
            'priority_number' => $emptyToNull($validated['priority_number'] ?? null),
        ];

        try {
            $weighment = $recordManual->handle($rake, $payload, (int) $user->id);
        } catch (InvalidArgumentException $e) {
            return back()
                ->withErrors(['total_net_weight_mt' => $e->getMessage()])
                ->withInput();
        }

        return to_route('weighments.show', $weighment->getKey())
            ->with('success', 'Manual weighment recorded. Upload the document when available.');
    }

    public function downloadTemplateXlsx(Request $request, RakeWeighmentExcelTemplateResolver $templateResolver): BinaryFileResponse|RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();

        $validated = $request->validate([
            'rake_id' => ['required', 'integer', 'exists:rakes,id'],
            'return_to' => ['nullable', 'string', 'max:2048'],
        ]);

        $rake = Rake::query()
            ->with('siding:id,name,code,station_code')
            ->findOrFail((int) $validated['rake_id']);

        abort_unless($user->canAccessSiding((int) $rake->siding_id), 403);

        $resolved = $templateResolver->resolve($rake);

        if (isset($resolved['error'])) {
            $message = match ($resolved['error']) {
                RakeWeighmentExcelTemplateResolver::ERROR_NO_SIDING => 'Assign a siding to this rake before downloading the template.',
                RakeWeighmentExcelTemplateResolver::ERROR_UNKNOWN_SIDING => 'Weighment Excel template is only available for Pakur, Dumka, or Kurwa sidings.',
                RakeWeighmentExcelTemplateResolver::ERROR_FILE_MISSING => 'The weighment template file is missing on the server. Please contact support.',
                default => 'Unable to download the weighment template.',
            };

            if ($resolved['error'] === RakeWeighmentExcelTemplateResolver::ERROR_FILE_MISSING) {
                Log::error('Weighment XLSX template file missing on disk.', [
                    'rake_id' => $rake->id,
                    'siding_id' => $rake->siding_id,
                ]);
            }

            return redirect($this->validatedInternalReturnTo($request))
                ->withErrors(['template' => $message]);
        }

        return response()->download($resolved['absolute_path'], $resolved['download_basename']);
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

    /**
     * Safe same-origin path for redirects (open-redirect safe): must start with "/" and must not be "//..." or contain a scheme.
     */
    private function validatedInternalReturnTo(Request $request): string
    {
        $raw = $request->query('return_to');
        if (! is_string($raw) || mb_trim($raw) === '') {
            return route('weighments.index');
        }

        $decoded = rawurldecode(mb_trim($raw));
        if (! str_starts_with($decoded, '/') || str_starts_with($decoded, '//') || str_contains($decoded, '://')) {
            return route('weighments.index');
        }

        return $decoded;
    }

    /**
     * When the client omits `filter[loading_date]`, restrict the hub to {@see Rake::$loading_date} equal to today.
     * If `filter[loading_date]` is present (even empty), do not inject a default.
     */
    private function applyWeighmentsHubDefaultLoadingDateFilter(Request $request): void
    {
        $filter = $request->input('filter', []);
        if (! is_array($filter)) {
            $filter = [];
        }
        if (array_key_exists('loading_date', $filter)) {
            return;
        }
        $filter['loading_date'] = 'eq:'.now()->toDateString();
        $request->merge(['filter' => $filter]);
    }

    private function assertUserCanAccessWeighmentRakeSiding(User $user, Weighment $weighment): void
    {
        $rake = $weighment->rake;
        abort_if($rake === null, 404);

        abort_unless($user->canAccessSiding((int) $rake->siding_id), 403);
    }

    /**
     * Same permission OR-list as the weighments hub delete UI (see weighments/index.tsx).
     */
    private function userMatchesWeighmentsHubDeletePermissions(User $user): bool
    {
        if ($user->can('bypass-permissions')) {
            return true;
        }

        return $user->hasPermissionTo('sections.weighments.delete')
            || $user->hasPermissionTo('sections.rakes.update')
            || $user->hasPermissionTo('sections.weighments.upload');
    }
}
