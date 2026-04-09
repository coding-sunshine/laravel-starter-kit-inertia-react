<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\Rake;

final class RakeRrHubPayload
{
    /**
     * Snapshot for Railway Receipt hub / rake show RR workflow (camelCase keys for Inertia parity).
     *
     * @return array{is_diverted: bool, rrDocuments: list<array<string, mixed>>, diverrtDestinations: list<array<string, mixed>>}
     */
    public static function fromRake(Rake $rake): array
    {
        $rake->loadMissing(['rrDocuments', 'diverrtDestinations']);

        return [
            'is_diverted' => (bool) $rake->is_diverted,
            'rrDocuments' => $rake->rrDocuments
                ->map(static function ($doc): array {
                    return [
                        'id' => $doc->id,
                        'rr_number' => $doc->rr_number,
                        'rr_received_date' => $doc->rr_received_date?->toIso8601String() ?? '',
                        'rr_weight_mt' => $doc->rr_weight_mt !== null ? (string) $doc->rr_weight_mt : null,
                        'document_status' => $doc->document_status,
                        'diverrt_destination_id' => $doc->diverrt_destination_id,
                    ];
                })
                ->values()
                ->all(),
            'diverrtDestinations' => $rake->diverrtDestinations
                ->map(static function ($row): array {
                    return [
                        'id' => $row->id,
                        'location' => $row->location,
                    ];
                })
                ->values()
                ->all(),
        ];
    }
}
