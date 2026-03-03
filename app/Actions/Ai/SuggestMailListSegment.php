<?php

declare(strict_types=1);

namespace App\Actions\Ai;

use App\Models\MailList;
use App\Services\PrismService;
use Throwable;

final readonly class SuggestMailListSegment
{
    public function __construct(private PrismService $prism) {}

    public function handle(MailList $mailList): ?string
    {
        if (! $this->prism->isAvailable()) {
            return null;
        }

        $mailList->loadMissing(['ownerContact']);

        $name = $mailList->name ?? '—';
        $owner = $mailList->ownerContact
            ? mb_trim($mailList->ownerContact->first_name.' '.$mailList->ownerContact->last_name)
            : '—';

        $prompt = <<<PROMPT
            Based on this mail list, suggest a target audience segment in 1-2 sentences.

            Mail list name: {$name}
            Owner contact: {$owner}
            PROMPT;

        try {
            $response = $this->prism->generate($prompt);

            return $response->text;
        } catch (Throwable) {
            return null;
        }
    }
}
