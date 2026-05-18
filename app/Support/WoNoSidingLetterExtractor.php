<?php

declare(strict_types=1);

namespace App\Support;

/**
 * Letters D|P|K extracted from WO NO fields ({@see TransportWorkOrderRegistrationSidingResolver}).
 */
final readonly class WoNoSidingLetterExtractor
{
    public function extractFromTwoFields(?string $workOrderNo1, ?string $workOrderNo2): ?string
    {
        $wo1 = $workOrderNo1 !== null ? mb_trim($workOrderNo1) : '';
        if ($wo1 !== '' && preg_match('/^([DPK])\d+$/i', $wo1, $m) === 1) {
            return mb_strtoupper($m[1]);
        }

        $wo2 = $workOrderNo2 !== null ? mb_trim($workOrderNo2) : '';
        if ($wo2 !== '') {
            if (preg_match('/WO-([DPK])\d+$/i', $wo2, $m) === 1) {
                return mb_strtoupper($m[1]);
            }

            if (preg_match('/^([DPK])\d+$/i', $wo2, $m) === 1) {
                return mb_strtoupper($m[1]);
            }
        }

        return null;
    }

    /**
     * Single WO NO cell + first-character D|P|K fallback (after trim).
     */
    public function extractFromWoNo(?string $woNo): ?string
    {
        $fromPair = $this->extractFromTwoFields($woNo, $woNo);
        if ($fromPair !== null) {
            return $fromPair;
        }

        $trimmed = $woNo !== null ? mb_trim($woNo) : '';
        if ($trimmed === '') {
            return null;
        }

        $first = mb_substr($trimmed, 0, 1);
        $firstUpper = mb_strtoupper($first);

        return in_array($firstUpper, ['D', 'P', 'K'], true) ? $firstUpper : null;
    }
}
