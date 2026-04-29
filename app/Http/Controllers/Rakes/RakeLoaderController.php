<?php

declare(strict_types=1);

namespace App\Http\Controllers\Rakes;

use App\DataTables\RakeLoaderListDataTable;
use App\Http\Controllers\Controller;
use App\Models\LoaderOperator;
use App\Models\Rake;
use App\Models\RakeWeighment;
use App\Models\Siding;
use App\Models\User;
use App\Services\SidingContext;
use App\Services\TenantContext;
use App\Support\Rakes\RakeLoaderWagonNumber;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final class RakeLoaderController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();
        abort_unless($this->hasSectionPermission($user, 'sections.rake_loader.view'), 403);

        $sidings = $this->rakeLoaderSidingOptions($user);

        $currentSiding = SidingContext::get();

        return Inertia::render('rake-loader/index', [
            'tableData' => RakeLoaderListDataTable::makeTable($request),
            'sidings' => $sidings,
            'defaultSidingId' => $currentSiding?->id,
            'isSuperAdmin' => $user->isSuperAdmin(),
            'loadError' => $request->session()->pull('rake_loader_error'),
        ]);
    }

    public function loading(Request $request, Rake $rake): Response|RedirectResponse
    {
        $user = $request->user();
        abort_unless($this->hasSectionPermission($user, 'sections.rake_loader.view'), 403);

        if (! $user->isSuperAdmin() && ! $user->canAccessSiding((int) $rake->siding_id)) {
            abort(403);
        }

        if (! RakeWeighment::query()->where('rake_id', $rake->id)->exists()) {
            return redirect()
                ->route('rake-loader.index')
                ->with('rake_loader_error', 'Add a rake weighment record before entering loader data.');
        }

        $rake->load([
            'siding:id,name,code',
            'siding.loaders:id,siding_id,loader_name,code',
            'wagons:id,rake_id,wagon_sequence,wagon_number,wagon_type,pcc_weight_mt,is_unfit',
            'wagonLoadings.wagon:id,wagon_number,wagon_sequence,wagon_type,pcc_weight_mt,is_unfit',
            'wagonLoadings.loader:id,loader_name,code',
        ]);

        return Inertia::render('rake-loader/loading', [
            'rake' => self::buildRakeLoaderRakePayload($rake),
        ]);
    }

    public function storeOverride(Request $request, Rake $rake): \Illuminate\Http\JsonResponse
    {
        $validated = $request->validate([
            'reason' => ['required', 'in:reduced_load,equipment_constraint,railway_instruction,other'],
            'notes' => ['nullable', 'string', 'max:500'],
            'overload_mt' => ['required', 'numeric', 'min:0'],
            'estimated_penalty_rs' => ['required', 'numeric', 'min:0'],
            'wagon_loading_id' => ['nullable', 'integer'],
        ]);

        /** @var User $user */
        $user = $request->user();

        (new \App\Actions\LogLoadingOverride)->handle(
            rake: $rake,
            operator: $user,
            reason: $validated['reason'],
            overloadMt: (float) $validated['overload_mt'],
            estimatedPenaltyRs: (float) $validated['estimated_penalty_rs'],
            notes: $validated['notes'] ?? null,
            wagonLoadingId: $validated['wagon_loading_id'] ?? null,
        );

        return response()->json(['logged' => true]);
    }

    /**
     * @return array<string, mixed>
     */
    private static function buildRakeLoaderRakePayload(Rake $rake): array
    {
        $wagons = $rake->wagons
            ->sortBy('wagon_sequence')
            ->values()
            ->reject(static fn ($w): bool => RakeLoaderWagonNumber::isWeighmentPlaceholder($w->wagon_number));

        $allowedWagonIds = $wagons->pluck('id')->flip();

        $wagonLoadings = $rake->wagonLoadings
            ->filter(static fn ($l): bool => $allowedWagonIds->has($l->wagon_id))
            ->sortBy(static fn ($l): int => $l->wagon?->wagon_sequence ?? $l->id)
            ->values();

        $sidingId = $rake->siding_id;
        $loaderOperatorOptions = LoaderOperator::query()
            ->where('is_active', true)
            ->where(function ($q) use ($sidingId): void {
                $q->whereNull('siding_id');
                if ($sidingId !== null) {
                    $q->orWhere('siding_id', $sidingId);
                }
            })
            ->orderBy('name')
            ->pluck('name')
            ->values()
            ->all();

        return [
            'id' => $rake->id,
            'rake_number' => $rake->rake_number,
            'loader_weighment_status' => $rake->loader_weighment_status,
            'loading_start_time' => $rake->loading_start_time?->toIso8601String(),
            'loading_end_time' => $rake->loading_end_time?->toIso8601String(),
            'wagons' => $wagons
                ->map(static fn ($w): array => [
                    'id' => $w->id,
                    'wagon_number' => $w->wagon_number,
                    'wagon_sequence' => $w->wagon_sequence,
                    'wagon_type' => $w->wagon_type,
                    'pcc_weight_mt' => $w->pcc_weight_mt,
                    'is_unfit' => (bool) $w->is_unfit,
                ])
                ->all(),
            'wagonLoadings' => $wagonLoadings
                ->map(static fn ($loading): array => [
                    'id' => $loading->id,
                    'wagon_id' => $loading->wagon_id,
                    'loader_id' => $loading->loader_id,
                    'loader_operator_name' => $loading->loader_operator_name,
                    'loaded_quantity_mt' => $loading->loaded_quantity_mt !== null ? (string) $loading->loaded_quantity_mt : '',
                    'loading_time' => $loading->loading_time?->toIso8601String(),
                    'remarks' => $loading->remarks,
                    'wagon' => $loading->wagon ? [
                        'wagon_number' => $loading->wagon->wagon_number,
                        'wagon_sequence' => $loading->wagon->wagon_sequence,
                        'wagon_type' => $loading->wagon->wagon_type,
                        'pcc_weight_mt' => $loading->wagon->pcc_weight_mt,
                    ] : null,
                    'loader' => $loading->loader ? [
                        'loader_name' => $loading->loader->loader_name,
                        'code' => $loading->loader->code,
                    ] : null,
                ])
                ->all(),
            'siding' => $rake->siding ? [
                'id' => $rake->siding->id,
                'name' => $rake->siding->name,
                'code' => $rake->siding->code,
                'loaders' => $rake->siding->loaders
                    ->map(static fn ($loader): array => [
                        'id' => $loader->id,
                        'loader_name' => $loader->loader_name,
                        'code' => $loader->code,
                    ])
                    ->values()
                    ->all(),
            ] : null,
            'loaderOperatorOptions' => $loaderOperatorOptions,
        ];
    }

    /**
     * @return list<array{id: int, name: string, code: string}>
     */
    private function rakeLoaderSidingOptions(User $user): array
    {
        if ($user->isSuperAdmin()) {
            return Siding::query()
                ->orderBy('name')
                ->get(['id', 'name', 'code'])
                ->map(static fn (Siding $s): array => [
                    'id' => $s->id,
                    'name' => $s->name,
                    'code' => $s->code,
                ])
                ->values()
                ->all();
        }

        $ids = $user->sidings()->pluck('sidings.id')->all();
        if ($ids === [] && $user->siding_id !== null) {
            $ids = [(int) $user->siding_id];
        }

        if ($ids === []) {
            return [];
        }

        return Siding::query()
            ->whereIn('id', $ids)
            ->orderBy('name')
            ->get(['id', 'name', 'code'])
            ->map(static fn (Siding $s): array => [
                'id' => $s->id,
                'name' => $s->name,
                'code' => $s->code,
            ])
            ->values()
            ->all();
    }

    private function hasSectionPermission(User $user, string $permission): bool
    {
        if ($user->can('bypass-permissions')) {
            return true;
        }

        if (TenantContext::check() && $user->canInCurrentOrganization($permission)) {
            return true;
        }

        return $user->hasPermissionTo($permission);
    }
}
