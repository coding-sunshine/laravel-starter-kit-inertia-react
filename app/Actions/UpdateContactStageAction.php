<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Contact;

final readonly class UpdateContactStageAction
{
    public function __construct(private UpdateLastContactedAtAction $updateLastContactedAt)
    {
        //
    }

    public function handle(Contact $contact, string $stage): void
    {
        $contact->stage = $stage;
        $contact->save();

        $this->updateLastContactedAt->handle($contact, 'stage_updated');
    }
}
