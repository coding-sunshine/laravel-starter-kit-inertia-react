<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Actions\ClassifyContactTypeAction;
use App\Models\Contact;
use Illuminate\Console\Command;

final class ClassifyContactTypesCommand extends Command
{
    protected $signature = 'fusion:classify-contacts
                            {--dry-run : Preview without updating contacts}
                            {--chunk=100 : Contacts per batch}
                            {--type=lead : Only classify contacts of this type}';

    protected $description = 'Use AI to classify contact types based on their profile and activity.';

    public function handle(ClassifyContactTypeAction $action): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $chunk = (int) $this->option('chunk');
        $type = $this->option('type');

        $query = Contact::query()->where('type', $type);
        $total = $query->count();

        $this->info("Classifying {$total} contacts with type '{$type}'...");

        $bar = $this->output->createProgressBar($total);
        $bar->start();

        $classified = 0;
        $unchanged = 0;
        $failed = 0;

        $query->chunk($chunk, function ($contacts) use ($action, $dryRun, &$classified, &$unchanged, &$failed, $bar) {
            foreach ($contacts as $contact) {
                $result = $action->handle($contact, persist: ! $dryRun);

                if ($result === null) {
                    $failed++;
                } elseif ($result['type'] !== $contact->getOriginal('type')) {
                    $classified++;
                } else {
                    $unchanged++;
                }

                $bar->advance();
            }
        });

        $bar->finish();
        $this->newLine();
        $this->info("Classified: {$classified} | Unchanged: {$unchanged} | Failed: {$failed}");

        return self::SUCCESS;
    }
}
