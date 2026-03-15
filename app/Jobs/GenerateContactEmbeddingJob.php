<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Actions\GenerateContactEmbeddingAction;
use App\Models\Contact;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

final class GenerateContactEmbeddingJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $backoff = 60;

    public function __construct(private readonly int $contactId)
    {
        $this->queue = 'ai';
    }

    public function handle(GenerateContactEmbeddingAction $action): void
    {
        $contact = Contact::query()->find($this->contactId);

        if ($contact === null) {
            return;
        }

        $action->handle($contact);
    }
}
