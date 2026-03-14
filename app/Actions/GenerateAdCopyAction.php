<?php

declare(strict_types=1);

namespace App\Actions;

use App\Services\AiCreditService;
use App\Services\PrismService;
use Throwable;

final readonly class GenerateAdCopyAction
{
    public function __construct(
        private PrismService $prism,
        private AiCreditService $aiCredits,
    ) {
        //
    }

    /**
     * @return array{headline: string, body_copy: string, cta_text: string}
     */
    public function handle(string $channel, string $type, string $tone, string $context): array
    {
        $user = auth()->user();

        if ($user === null || ! $this->prism->isAvailable() || ! $this->aiCredits->canUse($user, 'generate_ad_copy')) {
            return $this->fallback();
        }

        try {
            $response = $this->prism->text()
                ->withSystemPrompt("You are a real estate marketing copywriter. Generate compelling ad copy for {$channel} {$type} ads.")
                ->withPrompt("Generate ad copy for a {$channel} {$type} ad with a {$tone} tone. Context: {$context}\n\nReturn JSON with: headline (max 40 chars), body_copy (max 125 chars), cta_text (max 20 chars).")
                ->generate();

            $this->aiCredits->deduct($user, 'generate_ad_copy');

            $text = $response->text;
            $jsonStart = mb_strpos($text, '{');
            $jsonEnd = mb_strrpos($text, '}');

            if ($jsonStart !== false && $jsonEnd !== false) {
                $json = mb_substr($text, $jsonStart, $jsonEnd - $jsonStart + 1);
                $parsed = json_decode($json, true);

                if (is_array($parsed)) {
                    return [
                        'headline' => (string) ($parsed['headline'] ?? $this->fallback()['headline']),
                        'body_copy' => (string) ($parsed['body_copy'] ?? $this->fallback()['body_copy']),
                        'cta_text' => (string) ($parsed['cta_text'] ?? $this->fallback()['cta_text']),
                    ];
                }
            }
        } catch (Throwable) {
            // fallthrough to fallback
        }

        return $this->fallback();
    }

    /**
     * @return array{headline: string, body_copy: string, cta_text: string}
     */
    private function fallback(): array
    {
        return [
            'headline' => 'Discover Your Dream Property',
            'body_copy' => 'Explore premium real estate opportunities curated for you. Limited availability — act now.',
            'cta_text' => 'Learn More',
        ];
    }
}
