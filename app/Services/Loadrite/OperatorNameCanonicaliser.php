<?php

declare(strict_types=1);

namespace App\Services\Loadrite;

use Illuminate\Cache\Repository as CacheRepository;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Canonicalises operator name strings entered by loader operators.
 *
 * Operators type their name inconsistently: "SURESH", "suresh ",
 * "Suresh K", "Suresj" (typo). This service normalises input to a
 * consistent title-cased form and fuzzy-matches it against existing
 * LoaderOperator records so the scoreboard sees a single canonical name.
 *
 * Matching rules (applied in order):
 *  1. Trim, collapse whitespace, title-case the raw input.
 *  2. Exact match (case-insensitive) against existing operator names → return
 *     that existing canonical spelling.
 *  3. Levenshtein distance ≤ 2 against any existing operator name (compared on
 *     lowercase forms) → return the existing canonical spelling.
 *  4. No match → return the title-cased input (new operator, accept as-is).
 *  5. null / blank → return null.
 *
 * Operator catalog is cached for 5 minutes to avoid repeated DB hits.
 */
final readonly class OperatorNameCanonicaliser
{
    public const string CACHE_KEY = 'operator-canonicaliser:catalog:v1';

    public const int CACHE_TTL_SECONDS = 300; // 5 minutes

    public function __construct(
        private CacheRepository $cache,
    ) {}

    /**
     * Returns the canonical operator name. Title-case, trimmed, mapped to
     * existing LoaderOperator records when within Levenshtein 2.
     */
    public function canonicalise(?string $raw): ?string
    {
        if ($raw === null) {
            return null;
        }

        $normalised = $this->titleCase(mb_trim($raw));

        if ($normalised === '') {
            return null;
        }

        $existing = $this->loadCatalog();

        if ($existing === []) {
            // No operators on record — accept as new.
            return $normalised;
        }

        // Step 2: exact match (case-insensitive).
        $normalisedLower = mb_strtolower($normalised);

        foreach ($existing as $canonical) {
            if (mb_strtolower($canonical) === $normalisedLower) {
                return $canonical;
            }
        }

        // Step 3: Levenshtein ≤ 2.
        $bestMatch = null;
        $bestDist = PHP_INT_MAX;
        $tie = false;

        foreach ($existing as $canonical) {
            $dist = levenshtein($normalisedLower, mb_strtolower($canonical));

            if ($dist < $bestDist) {
                $bestDist = $dist;
                $bestMatch = $canonical;
                $tie = false;
            } elseif ($dist === $bestDist) {
                $tie = true;
            }
        }

        if ($bestDist <= 2 && ! $tie) {
            return $bestMatch;
        }

        // Step 4: genuine new operator.
        return $normalised;
    }

    /**
     * Load the operator name catalog from the cache or DB.
     *
     * @return list<string>
     */
    private function loadCatalog(): array
    {
        return $this->cache->remember(
            self::CACHE_KEY,
            self::CACHE_TTL_SECONDS,
            static fn (): array => DB::table('loader_operators')
                ->whereNotNull('name')
                ->pluck('name')
                ->map(static fn (string $n): string => mb_trim($n))
                ->filter(static fn (string $n): bool => $n !== '')
                ->values()
                ->all(),
        );
    }

    /**
     * Title-case a string: first letter of each word uppercased, rest lower.
     */
    private function titleCase(string $value): string
    {
        return Str::title($value);
    }
}
