<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;

final class ImportAllCommand extends Command
{
    /**
     * Import commands in dependency order.
     *
     * @var array<int, string>
     */
    private const array IMPORT_COMMANDS = [
        'fusion:import-contacts',
        'fusion:import-projects-lots',
        'fusion:import-users',
        'fusion:import-reservations-sales',
        'fusion:import-ai-bot-config',
        'fusion:import-media',
        'fusion:import-legacy-commissions',
        'fusion:import-login-histories',
        'fusion:import-websites',
    ];

    protected $signature = 'fusion:import-all
                            {--dry-run : Preview without writing to DB}
                            {--fresh : Run migrate:fresh --seed before importing}';

    protected $description = 'Run all Fusion import commands in dependency order.';

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $fresh = (bool) $this->option('fresh');

        if ($fresh && ! $dryRun) {
            $this->info('Running migrate:fresh --seed...');
            $this->call('migrate:fresh', ['--seed' => true, '--force' => true]);
            $this->newLine();
        }

        $this->info('Starting full Fusion import pipeline...');
        $this->newLine();

        $failed = 0;

        foreach (self::IMPORT_COMMANDS as $i => $command) {
            $step = $i + 1;
            $total = count(self::IMPORT_COMMANDS);
            $this->info("━━━ [{$step}/{$total}] {$command} ━━━");

            $options = ['--force' => true];
            if ($dryRun) {
                $options['--dry-run'] = true;
            }

            $exitCode = $this->call($command, $options);

            if ($exitCode !== self::SUCCESS) {
                $this->warn("  ⚠ {$command} reported failures.");
                $failed++;
            }

            $this->newLine();
        }

        if ($failed > 0) {
            $this->warn("{$failed} command(s) had failures. Check laravel.log for details.");

            return self::FAILURE;
        }

        $this->info('All imports completed successfully.');

        return self::SUCCESS;
    }
}
