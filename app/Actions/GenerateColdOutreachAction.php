<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\ColdOutreachTemplate;
use App\Services\PrismService;
use Throwable;

/**
 * Generate AI-powered cold outreach copy (email/SMS) using Prism.
 */
final readonly class GenerateColdOutreachAction
{
    public function __construct(private PrismService $prism)
    {
        //
    }

    /**
     * @param  array<string, mixed>  $context  Context for generation (contact info, property details, etc.)
     */
    public function handle(string $channel, string $tone, array $context, int $organizationId): ColdOutreachTemplate
    {
        $prompt = $this->buildPrompt($channel, $tone, $context);

        $responseText = '';

        if ($this->prism->isAvailable()) {
            try {
                $response = $this->prism->text()->withPrompt($prompt)->generate();
                $responseText = $response->text;
            } catch (Throwable) {
                $responseText = $this->fallbackCopy($channel, $tone, $context);
            }
        } else {
            $responseText = $this->fallbackCopy($channel, $tone, $context);
        }

        $parsed = $this->parseResponse($channel, $responseText, $context);

        return ColdOutreachTemplate::query()->create([
            'organization_id' => $organizationId,
            'name' => $context['name'] ?? 'AI Generated — '.now()->format('Y-m-d H:i'),
            'channel' => $channel,
            'subject' => $parsed['subject'] ?? null,
            'body' => $parsed['body'],
            'variants' => $parsed['variants'] ?? null,
            'ctas' => $parsed['ctas'] ?? null,
            'ai_generated' => true,
            'tone' => $tone,
        ]);
    }

    private function buildPrompt(string $channel, string $tone, array $context): string
    {
        $contextStr = collect($context)->map(fn ($v, $k) => "{$k}: {$v}")->implode("\n");

        return <<<PROMPT
        You are a real estate CRM copywriter. Generate a {$tone} {$channel} outreach template.

        Context:
        {$contextStr}

        Requirements:
        - {$channel} format
        - Tone: {$tone}
        - Include a clear call-to-action
        - Keep it concise and persuasive
        - For email: provide Subject and Body separately
        - For SMS: provide a single short message under 160 characters

        Format your response as:
        SUBJECT: <subject line if email>
        BODY: <message body>
        CTA: <call to action text>
        PROMPT;
    }

    /**
     * @param  array<string, mixed>  $context
     * @return array<string, mixed>
     */
    private function parseResponse(string $channel, string $text, array $context): array
    {
        $subject = null;
        $body = $text;
        $ctas = [];

        if (preg_match('/SUBJECT:\s*(.+)/i', $text, $m)) {
            $subject = mb_trim($m[1]);
        }

        if (preg_match('/BODY:\s*(.+)/si', $text, $m)) {
            $body = mb_trim($m[1]);
            // Strip anything after CTA
            $body = preg_replace('/\nCTA:.+/si', '', $body) ?? $body;
        }

        if (preg_match('/CTA:\s*(.+)/i', $text, $m)) {
            $ctas = [mb_trim($m[1])];
        }

        return ['subject' => $subject, 'body' => $body, 'ctas' => $ctas];
    }

    /**
     * @param  array<string, mixed>  $context
     */
    private function fallbackCopy(string $channel, string $tone, array $context): string
    {
        $firstName = $context['first_name'] ?? 'there';

        return match ($channel) {
            'sms' => "Hi {$firstName}, I'd love to help you find your perfect property. Reply to schedule a call!",
            default => "SUBJECT: Your property journey starts here\nBODY: Hi {$firstName},\n\nI wanted to reach out personally to discuss properties that match what you're looking for.\n\nCTA: Schedule a free consultation",
        };
    }
}
