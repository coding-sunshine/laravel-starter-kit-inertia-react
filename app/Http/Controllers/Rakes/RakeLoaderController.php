<?php

declare(strict_types=1);

namespace App\Http\Controllers\Rakes;

use App\Http\Controllers\Controller;
use App\Models\Rake;
use App\Models\RakeWeighment;
use App\Models\Siding;
use App\Services\SidingContext;
use App\Services\TenantContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final class RakeLoaderController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();
        abort_unless($this->hasSectionPermission($user, 'sections.rake_loader.view'), 403);

        $sidings = [];
        if ($user->isSuperAdmin()) {
            $sidings = Siding::query()
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

        $currentSiding = SidingContext::get();

        return Inertia::render('rake-loader/index', [
            'defaultDate' => now()->toDateString(),
            'sidings' => $sidings,
            'defaultSidingId' => $currentSiding?->id,
            'isSuperAdmin' => $user->isSuperAdmin(),
        ]);
    }

    public function rakes(Request $request): JsonResponse
    {
        $user = $request->user();
        abort_unless($this->hasSectionPermission($user, 'sections.rake_loader.view'), 403);

        $validated = $request->validate([
            'date' => ['required', 'date'],
            'siding_id' => ['nullable', 'integer', 'exists:sidings,id'],
        ]);

        $requestedSidingId = array_key_exists('siding_id', $validated) ? $validated['siding_id'] : null;
        $sidingId = $requestedSidingId !== null
            ? (int) $requestedSidingId
            : (int) (SidingContext::get()?->id ?? 0);

        if (! $user->isSuperAdmin() && ($sidingId === 0 || ! $user->canAccessSiding($sidingId))) {
            abort(403);
        }

        $date = $validated['date'];

        $rakes = Rake::query()
            ->where('siding_id', $sidingId)
            ->whereDate('loading_date', $date)
            ->orderBy('rake_number')
            ->get(['id', 'rake_number'])
            ->map(static fn (Rake $rake): array => [
                'id' => $rake->id,
                'rake_number' => $rake->rake_number,
            ])
            ->values()
            ->all();

        return response()->json([
            'rakes' => $rakes,
        ]);
    }

    public function show(Request $request, Rake $rake): JsonResponse
    {
        $user = $request->user();
        abort_unless($this->hasSectionPermission($user, 'sections.rake_loader.view'), 403);

        if (! $user->isSuperAdmin() && ! $user->canAccessSiding((int) $rake->siding_id)) {
            abort(403);
        }

        $hasWeighmentPdf = RakeWeighment::query()
            ->where('rake_id', $rake->id)
            ->whereNotNull('pdf_file_path')
            ->exists();

        if (! $hasWeighmentPdf) {
            return response()->json([
                'message' => 'Please Upload Weighment Data First.',
            ], 422);
        }

        $rake->load([
            'siding:id,name,code',
            'siding.loaders:id,siding_id,loader_name,code',
            'wagons:id,rake_id,wagon_sequence,wagon_number,wagon_type,pcc_weight_mt,is_unfit',
            'wagonLoadings.wagon:id,wagon_number,wagon_sequence,wagon_type,pcc_weight_mt,is_unfit',
            'wagonLoadings.loader:id,loader_name,code',
        ]);

        $wagons = $rake->wagons
            ->sortBy('wagon_sequence')
            ->values()
            ->reject(static fn ($w): bool => self::shouldSkipLoaderWeighmentWagonNumber($w->wagon_number));

        $allowedWagonIds = $wagons->pluck('id')->flip();

        $wagonLoadings = $rake->wagonLoadings
            ->filter(static fn ($l): bool => $allowedWagonIds->has($l->wagon_id))
            ->sortBy(static fn ($l): int => $l->wagon?->wagon_sequence ?? $l->id)
            ->values();

        return response()->json([
            'rake' => [
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
            ],
        ]);
    }

    private static function shouldSkipLoaderWeighmentWagonNumber(?string $wagonNumber): bool
    {
        $trimmed = $wagonNumber !== null ? mb_trim($wagonNumber) : '';

        return $trimmed !== '' && preg_match('/^W\d+$/', $trimmed) === 1;
    }

    private function hasSectionPermission(\App\Models\User $user, string $permission): bool
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
