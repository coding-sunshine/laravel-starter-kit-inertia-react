<?php

declare(strict_types=1);

namespace App\Services\Loadrite;

use Illuminate\Support\Facades\DB;

/**
 * Resolves a wagon's real type and permissible carrying capacity (CC) from
 * the fleet, given the wagon number and/or type abbreviation a Loadrite
 * operator keyed into the event UserData.
 *
 * Two-tier, evidence-based — no hardcoded capacities:
 *  1. Wagon-number match — the operator-keyed running number (e.g. 64209) is
 *     a suffix of a registered fleet wagon (e.g. ECOR64209). That fleet row
 *     carries the real type and CC.
 *  2. Type-abbreviation fallback — the keyed type code (HL, HSM1, HL2D…) is a
 *     suffix of full fleet wagon_type values (BOXNHL, BOBRNHSM1…). The
 *     count-weighted modal pcc of matching fleet wagons is used.
 *
 * Both tiers read only existing fleet data, so the CC is always traceable to
 * real wagons the client already operates.
 */
final class WagonCapacityResolver
{
    /** @var array<string, array{type: ?string, cc: ?float}> */
    private array $typeCache = [];

    /** @var array<string, array{type: ?string, cc: ?float, full_number: ?string}> */
    private array $wagonCache = [];

    /**
     * Resolve type + CC for a wagon, preferring an exact fleet wagon-number
     * match and falling back to the type abbreviation.
     *
     * @return array{cc: ?float, type: ?string, full_number: ?string, source: string}
     */
    public function resolve(?string $wagonNumber, ?string $typeAbbr): array
    {
        if ($wagonNumber !== null && $wagonNumber !== '') {
            $byNumber = $this->byWagonNumber($wagonNumber);
            if ($byNumber['cc'] !== null) {
                return [
                    'cc' => $byNumber['cc'],
                    'type' => $byNumber['type'],
                    'full_number' => $byNumber['full_number'],
                    'source' => 'fleet-wagon-match',
                ];
            }
        }

        if ($typeAbbr !== null && $typeAbbr !== '') {
            $byType = $this->byTypeAbbreviation($typeAbbr);
            if ($byType['cc'] !== null) {
                return [
                    'cc' => $byType['cc'],
                    'type' => $byType['type'],
                    'full_number' => null,
                    'source' => 'fleet-type-modal',
                ];
            }
        }

        return ['cc' => null, 'type' => null, 'full_number' => null, 'source' => 'unresolved'];
    }

    /**
     * @return array{type: ?string, cc: ?float, full_number: ?string}
     */
    private function byWagonNumber(string $wagonNumber): array
    {
        $key = mb_strtoupper($wagonNumber);
        if (isset($this->wagonCache[$key])) {
            return $this->wagonCache[$key];
        }

        $row = DB::table('wagons')
            ->whereRaw('UPPER(wagon_number) LIKE ?', ['%'.$key])
            ->whereRaw('pcc_weight_mt::numeric > 0')
            ->whereNotNull('wagon_type')
            ->orderByDesc('id')
            ->first(['wagon_number', 'wagon_type', 'pcc_weight_mt']);

        $result = $row !== null
            ? ['type' => $row->wagon_type, 'cc' => (float) $row->pcc_weight_mt, 'full_number' => $row->wagon_number]
            : ['type' => null, 'cc' => null, 'full_number' => null];

        return $this->wagonCache[$key] = $result;
    }

    /**
     * @return array{type: ?string, cc: ?float}
     */
    private function byTypeAbbreviation(string $abbr): array
    {
        $key = mb_strtoupper($abbr);
        if (isset($this->typeCache[$key])) {
            return $this->typeCache[$key];
        }

        $row = DB::table('wagons')
            ->whereRaw('UPPER(wagon_type) LIKE ?', ['%'.$key])
            ->whereRaw('pcc_weight_mt::numeric > 0')
            ->selectRaw('wagon_type, pcc_weight_mt::numeric AS cc, COUNT(*) AS n')
            ->groupBy('wagon_type', DB::raw('pcc_weight_mt::numeric'))
            ->orderByDesc('n')
            ->first();

        $result = $row !== null
            ? ['type' => $row->wagon_type, 'cc' => (float) $row->cc]
            : ['type' => null, 'cc' => null];

        return $this->typeCache[$key] = $result;
    }
}
