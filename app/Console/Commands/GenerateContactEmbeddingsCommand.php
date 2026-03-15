<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Actions\GenerateContactEmbeddingAction;
use App\Models\Contact;
use Illuminate\Console\Command;

final class GenerateContactEmbeddingsCommand extends Command
{
    protected $signature = 'fusion:generate-embeddings
                            {--model=contact : Which model to generate embeddings for}
                            {--chunk=100 : Contacts per batch}
                            {--fresh : Regenerate all embeddings, not just missing ones}';

    protected $description = 'Generate vector embeddings for contacts using OpenAI.';

    public function handle(GenerateContactEmbeddingAction $action): int
    {
        $chunk = (int) $this->option('chunk');
        $fresh = (bool) $this->option('fresh');

        $query = Contact::query();

        if (! $fresh) {
            $query->whereDoesntHave('embeddings', fn ($q) => $q->where('type', 'full'));
        }

        $total = $query->count();
        $this->info("Generating embeddings for {$total} contacts...");

        $bar = $this->output->createProgressBar($total);
        $bar->start();

        $success = 0;
        $failed = 0;

        $query->chunk($chunk, function ($contacts) use ($action, &$success, &$failed, $bar) {
            foreach ($contacts as $contact) {
                if ($action->handle($contact)) {
                    $success++;
                } else {
                    $failed++;
                }

                $bar->advance();
            }
        });

        $bar->finish();
        $this->newLine();
        $this->info("Success: {$success} | Failed: {$failed}");

        return $failed > 0 ? self::FAILURE : self::SUCCESS;
    }
}
