<?php

declare(strict_types=1);

namespace App\Services\Loadrite;

use Illuminate\Support\Facades\DB;

/**
 * Resolves a wagon's full type and official carrying capacity (CC).
 *
 * CC and tare come from the railway's official "CC WEIGHT" table
 * (config/loadrite.php → wagon_cc) — NOT from the fleet's pcc_weight_mt
 * column, which has known errors (it carried 79 MT for BOXNS where the
 * official CC is 70.70).
 *
 * The full wagon type is resolved two ways:
 *  1. Wagon-number match — the operator-keyed running number (e.g. 64209) is
 *     a suffix of a registered fleet wagon (ECOR64209); that fleet row's
 *     wagon_type is the precise type.
 *  2. Type-abbreviation map — the keyed short code (HL, HSM1, NS…) maps to a
 *     full type via config. Ambiguous codes default to the dominant variant.
 *
 * The CC is then a straight lookup of that type in the official table.
 */
final class WagonCapacityResolver
{
    /** @var array<string, array{type: ?string, full_number: ?string}> */
    private array $wagonCache = [];

    /**
     * @return array{cc: ?float, tare: ?float, type: ?string, full_number: ?string, source: string}
     */
    public function resolve(?string $wagonNumber, ?string $typeAbbr): array
    {
        // 1. Precise type from a fleet wagon-number match.
        $fullNumber = null;
        $type = null;
        $source = 'unresolved';

        if ($wagonNumber !== null && $wagonNumber !== '') {
            $byNumber = $this->typeByWagonNumber($wagonNumber);
            if ($byNumber['type'] !== null) {
                $type = $byNumber['type'];
                $fullNumber = $byNumber['full_number'];
                $source = 'fleet-wagon-match';
            }
        }

        // 2. Fall back to the keyed type abbreviation.
        if ($type === null && $this->isUsableTypeAbbreviation($typeAbbr)) {
            $mapped = $this->typeByAbbreviation($typeAbbr);
            if ($mapped !== null) {
                $type = $mapped;
                $source = 'type-abbreviation';
            }
        }

        $cap = $this->officialCapacity($type);

        return [
            'cc' => $cap['cc'],
            'tare' => $cap['tare'],
            'type' => $type,
            'full_number' => $fullNumber,
            'source' => $cap['cc'] !== null ? $source : 'unresolved',
        ];
    }

    /**
     * Official CC + tare for a full wagon type. Case-insensitive exact match
     * against the railway table.
     *
     * @return array{cc: ?float, tare: ?float}
     */
    private function officialCapacity(?string $type): array
    {
        if ($type === null) {
            return ['cc' => null, 'tare' => null];
        }

        $table = (array) config('loadrite.wagon_cc', []);
        $key = mb_strtoupper(mb_trim($type));

        foreach ($table as $name => $cap) {
            if (mb_strtoupper((string) $name) === $key) {
                return ['cc' => (float) $cap['cc'], 'tare' => (float) $cap['tare']];
            }
        }

        return ['cc' => null, 'tare' => null];
    }

    /**
     * @return array{type: ?string, full_number: ?string}
     */
    private function typeByWagonNumber(string $wagonNumber): array
    {
        $key = mb_strtoupper($wagonNumber);
        if (isset($this->wagonCache[$key])) {
            return $this->wagonCache[$key];
        }

        $row = DB::table('wagons')
            ->whereRaw('UPPER(wagon_number) LIKE ?', ['%'.$key])
            ->whereNotNull('wagon_type')
            ->orderByDesc('id')
            ->first(['wagon_number', 'wagon_type']);

        $result = $row !== null
            ? ['type' => $row->wagon_type, 'full_number' => $row->wagon_number]
            : ['type' => null, 'full_number' => null];

        return $this->wagonCache[$key] = $result;
    }

    private function typeByAbbreviation(string $abbr): ?string
    {
        $map = (array) config('loadrite.type_abbreviations', []);
        $key = mb_strtoupper(mb_trim($abbr));

        foreach ($map as $code => $fullType) {
            if (mb_strtoupper((string) $code) === $key) {
                return (string) $fullType;
            }
        }

        return null;
    }

    /**
     * Reject bare-number or single-character type tokens (operator mis-keys
     * like "2" or "11342"). A usable abbreviation contains a letter and is at
     * least two characters.
     */
    private function isUsableTypeAbbreviation(?string $abbr): bool
    {
        if ($abbr === null || mb_strlen($abbr) < 2) {
            return false;
        }

        return preg_match('/[A-Za-z]/', $abbr) === 1;
    }
}
