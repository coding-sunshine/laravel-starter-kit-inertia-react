<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\Siding;

/**
 * Resolves siding from work order numbers: leading D, P, or K (uppercase) = Dumka, Pakur, Kurwa.
 *
 * Supports short codes (e.g. Work Order No. 1: "D1") and long refs ending in .../WO-P123.
 */
final readonly class TransportWorkOrderRegistrationSidingResolver
{
    /**
     * Map first letter D|P|K to siding `code` used in {@see SidingSeeder}.
     */
    private const array LETTER_TO_SIDING_CODE = [
        'D' => 'DUMK',
        'P' => 'PKUR',
        'K' => 'KURWA',
    ];

    public function resolveSidingId(?string $workOrderNo1, ?string $workOrderNo2): ?int
    {
        $letter = $this->extractSidingLetter($workOrderNo1, $workOrderNo2);

        if ($letter === null) {
            return null;
        }

        $code = self::LETTER_TO_SIDING_CODE[$letter] ?? null;

        if ($code === null) {
            return null;
        }

        /** @var int|null */
        return Siding::query()->where('code', $code)->value('id');
    }

    private function extractSidingLetter(?string $workOrderNo1, ?string $workOrderNo2): ?string
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
}
