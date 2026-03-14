<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

final class VerifyImportProjectsLotsCommand extends Command
{
    // Expected counts from 11-verification-per-step.md § Step 3
    private const array DEFAULT_EXPECTED = [
        'projects' => 15381,
        'lots' => 120785,
        'developers' => 332,
        'projecttypes' => 12,
        'states' => 8,
        'suburbs' => 15299,
        'project_updates' => 153273,
        'flyers' => 10147,
        'flyer_templates' => 3,
    ];

    protected $signature = 'fusion:verify-import-projects-lots
                            {--baseline= : Path to baseline JSON file (default: storage/app/import-baseline.json)}';

    protected $description = 'Verify projects and lots import against expected row counts';

    public function handle(): int
    {
        $this->info('=== fusion:verify-import-projects-lots ===');

        $baseline = $this->loadBaseline();
        $allPass = true;

        // Check each table count
        foreach ($baseline as $table => $expected) {
            $actual = DB::table($table)->count();
            $pass = $actual === $expected;

            if ($pass) {
                $this->line("✅ {$table}: {$actual} rows (expected {$expected})");
            } else {
                $this->line("❌ {$table}: {$actual} rows (expected {$expected})");
                $allPass = false;
            }
        }

        // Check lots reference valid projects (no orphan lots)
        $orphanLots = DB::table('lots')
            ->leftJoin('projects', 'lots.project_id', '=', 'projects.id')
            ->whereNull('projects.id')
            ->count();

        if ($orphanLots === 0) {
            $this->line('✅ No orphan lots (all lots.project_id exist in projects)');
        } else {
            $this->line("❌ Orphan lots: {$orphanLots} lots have invalid project_id");
            $allPass = false;
        }

        // Summary
        $this->newLine();
        if ($allPass) {
            $this->info('OVERALL: PASS ✅');
        } else {
            $this->error('OVERALL: FAIL ❌');
        }

        return $allPass ? self::SUCCESS : self::FAILURE;
    }

    /**
     * Load baseline counts from file or use defaults from verification spec.
     *
     * @return array<string, int>
     */
    private function loadBaseline(): array
    {
        $baselinePath = $this->option('baseline') ?? storage_path('app/import-baseline.json');

        if (file_exists($baselinePath)) {
            $data = json_decode(file_get_contents($baselinePath), true);
            if (is_array($data) && isset($data['step3'])) {
                $this->comment("Using baseline from: {$baselinePath}");

                return $data['step3'];
            }
        }

        $this->comment('Using default expected counts from verification spec (11-verification-per-step.md)');

        return self::DEFAULT_EXPECTED;
    }
}
