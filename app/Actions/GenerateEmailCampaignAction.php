<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\EmailCampaign;
use App\Services\AiCreditService;
use App\Services\PrismService;
use Throwable;

final readonly class GenerateEmailCampaignAction
{
    public function __construct(
        private PrismService $prism,
        private AiCreditService $aiCredits,
    ) {
        //
    }

    /**
     * @return array{subject: string, preview_text: string, html_body: string, plain_text: string}
     */
    public function handle(EmailCampaign $campaign, string $recipientName = 'Valued Client'): array
    {
        $user = auth()->user();

        if ($user === null || ! $this->prism->isAvailable() || ! $this->aiCredits->canUse($user, 'generate_email_campaign')) {
            return $this->fallback($campaign, $recipientName);
        }

        try {
            $response = $this->prism->text()
                ->withSystemPrompt('You are a real estate email marketing specialist. Personalise email campaigns.')
                ->withPrompt("Personalise this email campaign for {$recipientName}:\nSubject: {$campaign->subject}\nContent: {$campaign->html_content}\n\nReturn JSON with: subject, preview_text, html_body (HTML), plain_text.")
                ->generate();

            $this->aiCredits->deduct($user, 'generate_email_campaign');

            $text = $response->text;
            $jsonStart = mb_strpos($text, '{');
            $jsonEnd = mb_strrpos($text, '}');

            if ($jsonStart !== false && $jsonEnd !== false) {
                $json = mb_substr($text, $jsonStart, $jsonEnd - $jsonStart + 1);
                $parsed = json_decode($json, true);

                if (is_array($parsed)) {
                    return [
                        'subject' => (string) ($parsed['subject'] ?? $campaign->subject),
                        'preview_text' => (string) ($parsed['preview_text'] ?? $campaign->preview_text ?? ''),
                        'html_body' => (string) ($parsed['html_body'] ?? $campaign->html_content ?? ''),
                        'plain_text' => (string) ($parsed['plain_text'] ?? $campaign->plain_text ?? ''),
                    ];
                }
            }
        } catch (Throwable) {
            // fallthrough to fallback
        }

        return $this->fallback($campaign, $recipientName);
    }

    /**
     * @return array{subject: string, preview_text: string, html_body: string, plain_text: string}
     */
    private function fallback(EmailCampaign $campaign, string $recipientName): array
    {
        return [
            'subject' => "Dear {$recipientName}: {$campaign->subject}",
            'preview_text' => $campaign->preview_text ?? 'Exclusive property opportunities await you.',
            'html_body' => $campaign->html_content ?? "<p>Dear {$recipientName},</p><p>We have exciting property news for you.</p>",
            'plain_text' => $campaign->plain_text ?? "Dear {$recipientName}, we have exciting property news for you.",
        ];
    }
}
