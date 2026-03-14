<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Contact;

final readonly class UpdateContactStageAction
{
    public function __construct(
        private UpdateLastContactedAtAction $updateLastContactedAt,
        private EvaluateAutomationRulesAction $evaluateRules,
    ) {
        //
    }

    public function handle(Contact $contact, string $stage): void
    {
        $contact->stage = $stage;
        $contact->save();

        $this->updateLastContactedAt->handle($contact, 'stage_updated');

        $this->evaluateRules->handle('contact.stage_changed', [
            'contact_id' => $contact->id,
            'stage' => $stage,
        ]);
    }
}
