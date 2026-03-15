<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Contact;
use App\Services\LeadScoringService;
use Illuminate\Console\Command;

final class RecalculateLeadScoresCommand extends Command
{
    protected $signature = 'fusion:recalculate-lead-scores
                            {--chunk=100 : Contacts per batch}';

    protected $description = 'Recalculate lead scores for all contacts using the hybrid scoring engine.';

    public function handle(LeadScoringService $scoring): int
    {
        $chunk = (int) $this->option('chunk');
        $total = Contact::query()->count();

        $this->info("Recalculating lead scores for {$total} contacts...");

        $bar = $this->output->createProgressBar($total);
        $bar->start();

        Contact::query()->chunk($chunk, function ($contacts) use ($scoring, $bar) {
            foreach ($contacts as $contact) {
                $scoring->refresh($contact);
                $bar->advance();
            }
        });

        $bar->finish();
        $this->newLine();
        $this->info('Lead scores recalculated.');

        return self::SUCCESS;
    }
}
