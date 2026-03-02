<?php

declare(strict_types=1);

namespace App\Console\Commands\Fusion;

use App\Models\Developer;
use App\Models\Flyer;
use App\Models\FlyerTemplate;
use App\Models\Lot;
use App\Models\PotentialProperty;
use App\Models\Project;
use App\Models\ProjectUpdate;
use App\Models\Projecttype;
use App\Models\Scopes\OrganizationScope;
use App\Models\SprRequest;
use App\Models\State;
use App\Models\Suburb;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Throwable;

final class VerifyImportProjectsLotsCommand extends Command
{
    protected $signature = 'fusion:verify-import-projects-lots
                            {--legacy=mysql_legacy : Legacy DB connection to compare counts}';

    protected $description = 'Verify projects/lots import: compare row counts and ensure no orphan lots.';

    public function handle(): int
    {
        $conn = $this->option('legacy');

        $tables = [
            ['projects', Project::withoutGlobalScope(OrganizationScope::class)->count(), 'projects'],
            ['lots', Lot::count(), 'lots'],
            ['developers', Developer::withoutGlobalScope(OrganizationScope::class)->count(), 'developers'],
            ['projecttypes', Projecttype::withoutGlobalScope(OrganizationScope::class)->count(), 'projecttypes'],
            ['states', State::withoutGlobalScope(OrganizationScope::class)->count(), 'states'],
            ['suburbs', Suburb::withoutGlobalScope(OrganizationScope::class)->count(), 'suburbs'],
            ['project_updates', ProjectUpdate::count(), 'project_updates'],
            ['flyers', Flyer::count(), 'flyers'],
            ['flyer_templates', FlyerTemplate::withoutGlobalScope(OrganizationScope::class)->count(), 'flyer_templates'],
        ];

        $sprCount = SprRequest::count();
        $potentialCount = PotentialProperty::withoutGlobalScope(OrganizationScope::class)->count();

        $rows = [];
        foreach ($tables as [$label, $newCount, $table]) {
            $legacyCount = $this->legacyCount($conn, $table);
            $rows[] = [$label, (string) $newCount, $legacyCount, $this->cmp($newCount, $legacyCount)];
        }
        $rows[] = ['spr_requests', (string) $sprCount, $this->legacyCount($conn, 'spr_requests'), $this->cmp($sprCount, $this->legacyCount($conn, 'spr_requests'))];
        $rows[] = ['potential_properties', (string) $potentialCount, $this->legacyCount($conn, 'potential_properties'), $this->cmp($potentialCount, $this->legacyCount($conn, 'potential_properties'))];

        $this->table(
            ['Table', 'New (PostgreSQL)', 'Legacy (MySQL)', 'OK?'],
            $rows
        );

        $orphanLots = Lot::whereNotExists(function ($query): void {
            $query->select(DB::raw(1))
                ->from('projects')
                ->whereColumn('projects.id', 'lots.project_id');
        })->count();

        if ($orphanLots > 0) {
            $this->error("Orphan lots: {$orphanLots} lots reference non-existent projects.");
            return self::FAILURE;
        }
        $this->info('No orphan lots (all lots reference valid projects).');

        $expectedProjects = $this->legacyCountInt($conn, 'projects');
        $expectedLots = $this->legacyCountInt($conn, 'lots');
        if ($expectedProjects !== null && Project::withoutGlobalScope(OrganizationScope::class)->count() !== $expectedProjects) {
            $this->warn('Project count does not match legacy. Re-run fusion:import-projects-lots if needed.');
            return self::FAILURE;
        }
        if ($expectedLots !== null && Lot::count() !== $expectedLots) {
            $this->warn('Lot count does not match legacy (excluding soft-deleted). Re-run fusion:import-projects-lots if needed.');
            return self::FAILURE;
        }

        $this->info('Verification PASS: projects and lots imported; no orphan lots.');
        return self::SUCCESS;
    }

    private function legacyCount(string $connection, string $table): string
    {
        $n = $this->legacyCountInt($connection, $table);
        return $n === null ? 'N/A (no connection)' : (string) $n;
    }

    private function legacyCountInt(string $connection, string $table): ?int
    {
        try {
            $query = DB::connection($connection)->table($table);
            if (in_array($table, ['lots', 'developers', 'projecttypes', 'spr_requests', 'flyers'], true)) {
                $query->whereNull('deleted_at');
            }
            return (int) $query->count();
        } catch (Throwable) {
            return null;
        }
    }

    /**
     * @param int|string $b
     */
    private function cmp(int $a, $b): string
    {
        if ($b === 'N/A (no connection)' || !is_numeric($b)) {
            return '—';
        }
        return $a === (int) $b ? '✓' : '✗';
    }
}
