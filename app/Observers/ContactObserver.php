<?php

declare(strict_types=1);

namespace App\Observers;

use App\Jobs\GenerateContactEmbeddingJob;
use App\Models\Contact;

final class ContactObserver
{
    public function created(Contact $contact): void
    {
        GenerateContactEmbeddingJob::dispatch($contact->id);
    }

    public function updated(Contact $contact): void
    {
        // Only regenerate embedding if meaningful fields changed
        $watchedFields = ['first_name', 'last_name', 'type', 'stage', 'company_name', 'job_title', 'contact_origin'];

        if ($contact->wasChanged($watchedFields)) {
            GenerateContactEmbeddingJob::dispatch($contact->id);
        }
    }
}
