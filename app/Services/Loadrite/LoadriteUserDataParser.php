<?php

declare(strict_types=1);

namespace App\Services\Loadrite;

/**
 * Extracts the rake number, wagon number and wagon type that operators key
 * into a Loadrite event's UserData1-4 fields.
 *
 * The slot each value occupies differs per scale (see config/loadrite.php).
 * When a scale is not configured, a heuristic is used:
 *   - type   = the token containing at least one letter (HL, HSM1, HL2D…)
 *   - rake   = a purely-numeric token with value <= 999
 *   - wagon  = a purely-numeric token with value >= 1000 (the larger one)
 *
 * Parsed wagon_type values are normalised through WagonTypeNormaliser before
 * being returned. If the raw value cannot be confidently matched to a catalog
 * entry, wagon_type will be null (data-quality concern surfaced by anomaly
 * tracking).
 *
 * Parsed operator values are canonicalised through OperatorNameCanonicaliser
 * before being returned so the scoreboard groups the same person consistently.
 */
final class LoadriteUserDataParser
{
    public function __construct(
        private readonly WagonTypeNormaliser $typeNormaliser = new WagonTypeNormaliser,
        private readonly ?OperatorNameCanonicaliser $operatorCanonicaliser = null,
    ) {}

    /**
     * @param  array<string, mixed>  $event  raw Loadrite event
     * @return array{rake_number: ?string, wagon_number: ?string, wagon_type: ?string, operator: ?string}
     */
    public function parse(array $event): array
    {
        $scaleId = isset($event['Scale ID']) ? (string) $event['Scale ID'] : '';
        $layouts = (array) config('loadrite.scale_layouts', []);

        if (isset($layouts[$scaleId])) {
            return $this->parseByLayout($event, (array) $layouts[$scaleId]);
        }

        return $this->parseByHeuristic($event);
    }

    /**
     * @param  array<string, mixed>  $event
     * @param  array<string, string>  $layout
     * @return array{rake_number: ?string, wagon_number: ?string, wagon_type: ?string, operator: ?string}
     */
    private function parseByLayout(array $event, array $layout): array
    {
        $rawOperator = $this->field($event, $layout['operator'] ?? null)
            ?? $this->field($event, 'Operator');

        return [
            'rake_number' => $this->field($event, $layout['rake'] ?? null),
            'wagon_number' => $this->field($event, $layout['wagon'] ?? null),
            'wagon_type' => $this->typeNormaliser->normalise($this->field($event, $layout['type'] ?? null)),
            'operator' => $this->canonicaliseOperator($rawOperator),
        ];
    }

    /**
     * @param  array<string, mixed>  $event
     * @return array{rake_number: ?string, wagon_number: ?string, wagon_type: ?string, operator: ?string}
     */
    private function parseByHeuristic(array $event): array
    {
        $tokens = [];
        foreach (['UserData1', 'UserData2', 'UserData3', 'UserData4'] as $key) {
            $v = $this->field($event, $key);
            if ($v !== null) {
                $tokens[] = $v;
            }
        }

        $rawType = null;
        $rake = null;
        $wagon = null;
        $operator = $this->field($event, 'Operator');

        foreach ($tokens as $t) {
            if (! is_numeric($t) && preg_match('/[A-Za-z]/', $t) === 1) {
                // Alphabetic token: a short type code, else an operator name.
                if (mb_strlen($t) <= 6 && $rawType === null) {
                    $rawType = $t;
                } elseif ($operator === null) {
                    $operator = $t;
                }

                continue;
            }
            if (is_numeric($t)) {
                $n = (int) $t;
                if ($n > 0 && $n <= 999 && $rake === null) {
                    $rake = $t;
                } elseif ($wagon === null) {
                    $wagon = $t;
                }
            }
        }

        return [
            'rake_number' => $rake,
            'wagon_number' => $wagon,
            'wagon_type' => $this->typeNormaliser->normalise($rawType),
            'operator' => $this->canonicaliseOperator($operator),
        ];
    }

    /**
     * Canonicalise the operator name through the injected canonicaliser when
     * available. Falls back to returning the raw value unchanged (null-safe).
     */
    private function canonicaliseOperator(?string $raw): ?string
    {
        if ($raw === null) {
            return null;
        }

        if ($this->operatorCanonicaliser === null) {
            return $raw;
        }

        return $this->operatorCanonicaliser->canonicalise($raw);
    }

    /**
     * @param  array<string, mixed>  $event
     */
    private function field(array $event, ?string $key): ?string
    {
        if ($key === null || ! isset($event[$key])) {
            return null;
        }

        $value = mb_trim((string) $event[$key]);

        return $value === '' ? null : $value;
    }
}
