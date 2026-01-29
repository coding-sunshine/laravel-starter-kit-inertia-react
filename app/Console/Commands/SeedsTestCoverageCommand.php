<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\SeedScenarioManager;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

final class SeedsTestCoverageCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'seeds:test-coverage
                            {--json : Output as JSON}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Analyze test coverage of seed scenarios';

    /**
     * Execute the console command.
     */
    public function handle(SeedScenarioManager $scenarioManager): int
    {
        $testsPath = base_path('tests');
        $scenarios = $scenarioManager->loadScenarios();

        if (! File::isDirectory($testsPath)) {
            $this->error('Tests directory not found.');

            return self::FAILURE;
        }

        $this->info('Analyzing test coverage...');
        $this->newLine();

        $testFiles = File::allFiles($testsPath);
        $usedModels = [];
        $usedRelationships = [];
        $usedScenarios = [];

        foreach ($testFiles as $file) {
            if ($file->getExtension() !== 'php') {
                continue;
            }

            $content = File::get($file->getPathname());

            // Find model references
            if (preg_match_all('/use\s+App\\\Models\\\\(\w+)/', $content, $matches)) {
                foreach ($matches[1] as $model) {
                    $usedModels[$model] = ($usedModels[$model] ?? 0) + 1;
                }
            }

            // Find relationship accesses
            if (preg_match_all('/\$(\w+)->(\w+)/', $content, $relMatches)) {
                foreach ($relMatches[2] as $rel) {
                    if (! in_array($rel, ['id', 'name', 'email', 'created_at', 'updated_at'], true)) {
                        $usedRelationships[$rel] = ($usedRelationships[$rel] ?? 0) + 1;
                    }
                }
            }

            // Find scenario usage
            if (preg_match_all('/seedScenario\([\'"](\w+)[\'"]/', $content, $scenarioMatches)) {
                foreach ($scenarioMatches[1] as $scenario) {
                    $usedScenarios[$scenario] = ($usedScenarios[$scenario] ?? 0) + 1;
                }
            }
        }

        $report = [
            'models_used' => $usedModels,
            'relationships_used' => $usedRelationships,
            'scenarios_used' => $usedScenarios,
            'defined_scenarios' => array_keys($scenarios),
            'missing_scenarios' => [],
        ];

        // Check for models used but not in scenarios
        foreach ($usedModels as $model => $count) {
            $found = false;

            foreach ($scenarios as $scenario) {
                $models = $scenario['models'] ?? [];

                foreach ($models as $modelConfig) {
                    $modelClass = $modelConfig['class'] ?? '';

                    if (Str::endsWith($modelClass, "\\{$model}")) {
                        $found = true;
                        break 2;
                    }
                }
            }

            if (! $found) {
                $report['missing_scenarios'][] = "Model {$model} is used in tests but has no scenario";
            }
        }

        if ($this->option('json')) {
            $this->line(json_encode($report, JSON_PRETTY_PRINT));

            return self::SUCCESS;
        }

        $this->info('Test Coverage Report:');
        $this->newLine();

        $this->line('Models used in tests:');
        foreach ($usedModels as $model => $count) {
            $this->line("  - {$model}: {$count} references");
        }

        $this->newLine();
        $this->line('Relationships accessed:');
        foreach ($usedRelationships as $rel => $count) {
            $this->line("  - {$rel}: {$count} accesses");
        }

        $this->newLine();
        $this->line('Scenarios used:');
        foreach ($usedScenarios as $scenario => $count) {
            $this->line("  - {$scenario}: {$count} uses");
        }

        $this->newLine();
        $this->line('Defined scenarios:');
        foreach (array_keys($scenarios) as $name) {
            $this->line("  - {$name}");
        }

        if (isset($report['missing_scenarios']) && $report['missing_scenarios'] !== []) {
            $this->newLine();
            $this->warn('Missing scenarios:');
            foreach ($report['missing_scenarios'] as $missing) {
                $this->line("  - {$missing}");
            }
        }

        return self::SUCCESS;
    }
}
