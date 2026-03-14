<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Contact;
use Illuminate\Support\Facades\Log;

final readonly class UpdateLastContactedAtAction
{
    public function handle(Contact $contact, string $reason): void
    {
        $contact->last_contacted_at = now();
        $contact->save();

        Log::info('Contact last_contacted_at updated', [
            'contact_id' => $contact->id,
            'reason' => $reason,
        ]);
    }
}
