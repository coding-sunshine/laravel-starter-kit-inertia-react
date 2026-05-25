<?php

declare(strict_types=1);

namespace App\Services\Loadrite;

/**
 * Normalises operator-keyed wagon type strings to the canonical catalog entry.
 *
 * Operators key wagon types inconsistently: `HL2D`, `hl2d`, `HL-2D`,
 * `HL2 D`, etc. This service strips separators, lowercases, and matches
 * against the official catalog (config/loadrite.php → wagon_cc keys).
 *
 * Matching is performed in two steps:
 *   1. Exact match after stripping non-alphanumeric characters.
 *   2. Levenshtein distance ≤ 2 against every catalog key, provided the
 *      best match is strictly lower than the next-best (unambiguous winner).
 *
 * Returns the canonical uppercase catalog key or null when no confident
 * match exists (including empty/whitespace input and tie situations).
 */
final readonly class WagonTypeNormaliser
{
    /**
     * Returns the canonical wagon type (uppercase, no separators, matched
     * against the known catalog) or null when no confident match exists.
     */
    public function normalise(?string $raw): ?string
    {
        if ($raw === null) {
            return null;
        }

        $stripped = $this->strip($raw);

        if ($stripped === '') {
            return null;
        }

        $catalog = $this->catalog();

        if ($catalog === []) {
            return null;
        }

        // Step 1: exact match after stripping.
        $upperStripped = mb_strtoupper($stripped);

        foreach ($catalog as $canonical) {
            if (mb_strtoupper($this->strip($canonical)) === $upperStripped) {
                return $canonical;
            }
        }

        // Step 2: Levenshtein — pick best only if strictly lower than next-best.
        $distances = [];

        foreach ($catalog as $canonical) {
            $distances[$canonical] = levenshtein(
                mb_strtolower($stripped),
                mb_strtolower($this->strip($canonical)),
            );
        }

        asort($distances);
        $sorted = array_values(array_keys($distances));
        $sortedDistances = array_values($distances);

        $best = $sortedDistances[0];

        if ($best > 2) {
            return null;
        }

        // Tie check: must be strictly lower than the second-best distance.
        if (isset($sortedDistances[1]) && $sortedDistances[1] === $best) {
            return null;
        }

        return $sorted[0];
    }

    /**
     * Returns the list of canonical wagon types from config.
     *
     * The catalog comprises:
     *   - Full wagon types: the keys of config('loadrite.wagon_cc')
     *   - Short abbreviations: the keys of config('loadrite.type_abbreviations')
     *
     * Both are valid canonical identifiers; full types are used by analytics
     * aggregations, abbreviations by the capacity resolver's abbreviation path.
     * Including both ensures that operators who key a short code ("HL2D") get
     * back the canonical uppercase abbreviation, and operators who key a full
     * type with separators ("BOXN-HL2D") get back the full canonical form.
     *
     * @return list<string>
     */
    public function catalog(): array
    {
        $fullTypes = array_keys((array) config('loadrite.wagon_cc', []));
        $abbreviations = array_keys((array) config('loadrite.type_abbreviations', []));

        return array_values(array_unique(array_merge($fullTypes, $abbreviations)));
    }

    /**
     * Strip non-alphanumeric characters and collapse whitespace.
     */
    private function strip(string $value): string
    {
        return (string) preg_replace('/[^A-Za-z0-9]/', '', $value);
    }
}
