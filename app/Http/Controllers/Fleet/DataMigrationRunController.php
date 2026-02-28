<?php

declare(strict_types=1);

namespace App\Http\Controllers\Fleet;

use App\Http\Controllers\Controller;
use App\Models\Fleet\DataMigrationRun;
use App\Services\TenantContext;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final class DataMigrationRunController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', DataMigrationRun::class);
        $orgId = TenantContext::id();
        $runs = DataMigrationRun::query()
            ->with(['organization', 'triggeredBy'])
            ->where('organization_id', $orgId)
            ->orderByDesc('started_at')
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('Fleet/DataMigrationRuns/Index', [
            'dataMigrationRuns' => $runs,
        ]);
    }

    public function show(DataMigrationRun $data_migration_run): Response
    {
        $this->authorize('view', $data_migration_run);
        $data_migration_run->load(['organization', 'triggeredBy']);

        return Inertia::render('Fleet/DataMigrationRuns/Show', ['dataMigrationRun' => $data_migration_run]);
    }
}
