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

    public function __construct(
        private WoNoSidingLetterExtractor $letterExtractor = new WoNoSidingLetterExtractor,
    ) {}

    public function resolveSidingId(?string $workOrderNo1, ?string $workOrderNo2): ?int
    {
        return $this->sidingIdFromLetter(
            $this->letterExtractor->extractFromTwoFields($workOrderNo1, $workOrderNo2),
        );
    }

    /**
     * Resolve siding from a single WO NO cell (vehicles spreadsheet): same D/P/K regex rules as
     * {@see resolveSidingId()} with both args, plus first-character D/P/K fallback after trim.
     */
    public function resolveSidingIdFromWoNo(?string $woNo): ?int
    {
        return $this->sidingIdFromLetter($this->letterExtractor->extractFromWoNo($woNo));
    }

    private function sidingIdFromLetter(?string $letter): ?int
    {
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
}
