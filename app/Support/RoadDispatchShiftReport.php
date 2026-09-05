<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\Siding;
use Illuminate\Support\Collection;

final class RoadDispatchShiftReport
{
    /** @var list<string> */
    public const SIDING_CODES = ['PKUR', 'KURWA', 'DUMK'];

    public const MAX_SPAN_DAYS = 93;

    /**
     * Sidings that appear in the shift completion report (Pakur, Kurwa, Dumka order).
     *
     * @return Collection<int, Siding>
     */
    public static function orderedReportSidings(): Collection
    {
        return Siding::query()
            ->whereIn('code', self::SIDING_CODES)
            ->get()
            ->sortBy(function (Siding $siding): int {
                $code = mb_strtoupper((string) $siding->code);
                $pos = array_search($code, self::SIDING_CODES, true);

                return $pos === false ? 99 : (int) $pos;
            })
            ->values();
    }

    public static function isAllowedSidingCode(string $code): bool
    {
        return in_array(mb_strtoupper($code), self::SIDING_CODES, true);
    }
}
