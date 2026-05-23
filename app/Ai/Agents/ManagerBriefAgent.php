<?php

declare(strict_types=1);

namespace App\Ai\Agents;

use App\DataTransferObjects\ManagerBrief\ActionCard;
use App\DataTransferObjects\ManagerBrief\Signal;
use App\Services\PrismService;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Throwable;

/**
 * AI agent that synthesises up to 5 ActionCard DTOs from ranked Signal DTOs.
 *
 * Calls the LLM via PrismService::text() with a single prompt that enumerates
 * the top-15 signals and instructs the model to respond with a JSON array of
 * action cards. The raw JSON is validated locally — invalid cards are dropped
 * silently and the survivors are clamped to 5.
 *
 * Deep-link rubric injected into the prompt:
 *   - operator_anomaly          → /dashboard?section=loader-overload&operator={name}
 *   - force_majeure             → /disputes
 *   - demurrage_risk            → /control-panel
 *   - overload_exposure         → /dashboard
 *   - scale_silence             → /dashboard
 *   - pending_override          → /dashboard
 *   - underloading_trend        → /dashboard
 *   - operator_recurring_risk   → /dashboard?section=loader-overload&operator={operator_name}
 *   - penalty_trajectory        → /dashboard?section=penalties-daily
 *   - demurrage_turnaround_risk → /dashboard?section=rake-operations
 *   - pcc_drift                 → /master-data/sidings
 */
final readonly class ManagerBriefAgent
{
    /**
     * Allowed deep-link path prefixes.
     *
     * Only internal route prefixes are permitted. Anything outside this list
     * (including protocol-relative URLs or dangerous schemes) is rejected to
     * prevent open-redirect and XSS vectors when the React side calls router.visit().
     *
     * @var list<string>
     */
    private const array ALLOWED_DEEP_LINK_PREFIXES = [
        '/dashboard',
        '/manager-brief',
        '/disputes',
        '/penalties',
        '/control-panel',
        '/control-panel-2',
        '/control-room',
        '/rake-loader',
        '/rakes',
        '/sidings',
        '/loading-overrides',
        '/master-data',
    ];

    /**
     * Dangerous scheme fragments that must never appear in a deep-link (case-insensitive).
     *
     * @var list<string>
     */
    private const array BLOCKED_SCHEMES = [
        'javascript:',
        'data:',
        'vbscript:',
        'file:',
    ];

    public function __construct(
        private PrismService $prism,
    ) {}

    /**
     * Synthesise action cards from ranked signals.
     *
     * @param  list<Signal>  $signals  Top-15 ranked signals from RankSignals
     * @param  array<string, mixed>  $context  Optional context (e.g. siding name, recent_period_label)
     * @return ?list<ActionCard> null on failure, array of up to 5 cards on success
     */
    public function synthesise(array $signals, array $context = []): ?array
    {
        try {
            $prompt = $this->buildPrompt($signals, $context);

            // Use defaultModel (config: prism.defaults.model — currently a free
            // OpenRouter route, e.g. deepseek/deepseek-r1-0528:free) so manager
            // briefs do not incur paid LLM cost. fastModel is reserved for
            // latency-sensitive paid flows.
            $response = $this->prism
                ->text($this->prism->defaultModel())
                ->withPrompt($prompt)
                ->asText();

            return $this->parseAndValidate($response->text);
        } catch (Throwable $e) {
            Log::warning('manager-brief: AI synthesis failed', [
                'exception' => $e->getMessage(),
                'signal_count' => count($signals),
            ]);

            return null;
        }
    }

    /**
     * Build the LLM prompt from signals and optional context.
     *
     * @param  list<Signal>  $signals
     * @param  array<string, mixed>  $context
     */
    private function buildPrompt(array $signals, array $context): string
    {
        $sidingLabel = isset($context['siding_name'])
            ? "Siding: {$context['siding_name']}."
            : '';

        $periodLabel = isset($context['recent_period_label'])
            ? "Recent period: {$context['recent_period_label']}."
            : '';

        $signalList = $this->formatSignals($signals);

        return <<<PROMPT
        You are an operations-room manager assistant for a railway loading siding. {$sidingLabel} {$periodLabel}

        Below are the top operational signals detected in the current shift, ordered by financial priority:

        {$signalList}

        Your task: Return a JSON array of up to 5 action cards that a shift manager should act on right now. Prioritise by severity and financial impact.

        Each action card MUST have exactly these fields (no extra fields):
        - severity: one of "critical", "high", "medium", "low" (lowercase only)
        - title: short actionable heading (under 60 characters)
        - why: 1-2 sentences explaining the issue and its impact
        - rs_at_stake: estimated revenue-share at risk as a non-negative number
        - deep_link: a relative path inside the app the manager should navigate to (MUST start with /)
        - deadline: ISO 8601 datetime string or null if no hard deadline

        Deep-link rubric — use these paths as guidance:
        - operator_anomaly          → /dashboard?section=loader-overload&operator={operator_name_from_payload}
        - force_majeure             → /disputes
        - demurrage_risk            → /control-panel
        - overload_exposure         → /dashboard
        - scale_silence             → /dashboard
        - pending_override          → /dashboard
        - underloading_trend        → /dashboard
        - operator_recurring_risk   → /dashboard?section=loader-overload&operator={operator_name_from_payload}
        - penalty_trajectory        → /dashboard?section=penalties-daily
        - demurrage_turnaround_risk → /dashboard?section=rake-operations
        - pcc_drift                 → /master-data/sidings

        Forecast signal types (operator_recurring_risk, penalty_trajectory, demurrage_turnaround_risk, pcc_drift) project future risk from historical patterns — narrate with hedges like "projected", "trending", or "if pattern continues".

        Return ONLY a valid JSON array. No markdown fences, no commentary. Example structure:
        [{"severity":"high","title":"Example action","why":"Because X is happening.","rs_at_stake":120.5,"deep_link":"/dashboard","deadline":null}]
        PROMPT;
    }

    /**
     * Format the signal list as a numbered enumeration for the prompt.
     *
     * @param  list<Signal>  $signals
     */
    private function formatSignals(array $signals): string
    {
        if ($signals === []) {
            return '(no signals)';
        }

        $lines = [];

        foreach ($signals as $index => $signal) {
            $payloadSummary = $this->summarisePayload($signal->payload);
            $lines[] = sprintf(
                '%d. type=%s | severity=%s | rs_at_stake=%.2f%s',
                $index + 1,
                $signal->type,
                $signal->severity,
                $signal->rsAtStake,
                $payloadSummary !== '' ? " | {$payloadSummary}" : '',
            );
        }

        return implode("\n", $lines);
    }

    /**
     * Summarise the key fields of a payload for inclusion in the prompt.
     *
     * @param  array<string, mixed>  $payload
     */
    private function summarisePayload(array $payload): string
    {
        if ($payload === []) {
            return '';
        }

        $parts = [];

        foreach ($payload as $key => $value) {
            if (is_scalar($value)) {
                $parts[] = "{$key}={$value}";
            }
        }

        return implode(', ', array_slice($parts, 0, 5));
    }

    /**
     * Parse the LLM response text as JSON and validate each card.
     *
     * Invalid cards are dropped silently. Survivors are clamped to 5.
     *
     * @return list<ActionCard>
     */
    private function parseAndValidate(string $rawText): array
    {
        $decoded = json_decode(mb_trim($rawText), true);

        if (! is_array($decoded)) {
            throw new RuntimeException('LLM response is not a valid JSON array');
        }

        $cards = [];

        foreach ($decoded as $item) {
            if (! is_array($item)) {
                continue;
            }

            $card = $this->validateCard($item);

            if ($card !== null) {
                $cards[] = $card;
            }

            if (count($cards) === 5) {
                break;
            }
        }

        return $cards;
    }

    /**
     * Validate a single raw card array and return an ActionCard or null if invalid.
     *
     * @param  array<string, mixed>  $item
     */
    private function validateCard(array $item): ?ActionCard
    {
        $allowedSeverities = ['critical', 'high', 'medium', 'low'];

        $severity = $item['severity'] ?? null;

        if (! is_string($severity) || ! in_array($severity, $allowedSeverities, true)) {
            return null;
        }

        $title = $item['title'] ?? null;

        if (! is_string($title) || $title === '') {
            return null;
        }

        $why = $item['why'] ?? null;

        if (! is_string($why) || $why === '') {
            return null;
        }

        $rsAtStake = $item['rs_at_stake'] ?? null;

        if (! is_numeric($rsAtStake) || (float) $rsAtStake < 0) {
            return null;
        }

        $deepLink = $item['deep_link'] ?? null;

        if (! is_string($deepLink) || ! $this->isDeepLinkSafe($deepLink)) {
            return null;
        }

        $deadline = $item['deadline'] ?? null;

        if ($deadline !== null && ! is_string($deadline)) {
            return null;
        }

        return new ActionCard(
            severity: $severity,
            title: $title,
            why: $why,
            rsAtStake: (float) $rsAtStake,
            deepLink: $deepLink,
            deadline: $deadline,
        );
    }

    /**
     * Return true only when the deep-link is a safe internal path.
     *
     * Rejects:
     *  - protocol-relative URLs (starting with //)
     *  - any path containing blocked schemes (javascript:, data:, vbscript:, file:)
     *  - paths that do not match an allowed prefix
     */
    private function isDeepLinkSafe(string $deepLink): bool
    {
        // Reject protocol-relative URLs (//evil.com/…).
        if (str_starts_with($deepLink, '//')) {
            return false;
        }

        // Reject blocked schemes regardless of casing.
        $lower = mb_strtolower($deepLink);

        foreach (self::BLOCKED_SCHEMES as $scheme) {
            if (str_contains($lower, $scheme)) {
                return false;
            }
        }

        // Must match one of the allowed internal route prefixes.
        foreach (self::ALLOWED_DEEP_LINK_PREFIXES as $prefix) {
            if (str_starts_with($deepLink, $prefix)) {
                return true;
            }
        }

        return false;
    }
}
