<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Siding;
use App\Models\User;
use App\Services\Railway\RrImportService;
use App\Services\Railway\RrParserService;
use Illuminate\Http\UploadedFile;
use InvalidArgumentException;

final readonly class PreviewRailwayReceiptImport
{
    public function __construct(
        private RrParserService $parser,
        private ResolveRakeForRrImportPreview $resolveRakeForRrImportPreview,
        private RrImportService $rrImportService,
    ) {}

    /**
     * Parse an RR PDF, resolve rake by FNR/e-demand, ensure siding access, ensure the default RR upload slot is free
     * (parity with import without `diverrt_destination_id`), then return the preview payload.
     *
     * @return array{
     *     fnr_from_rr: string,
     *     fnr_from_indent: string|null,
     *     to_station_code: string|null,
     *     rake_destination_code: string|null,
     *     rake_destination: string|null,
     *     siding_code: string|null,
     *     siding_name: string|null,
     *     rake_id: int,
     *     rake_number: string|null,
     *     rake_serial_number: string|null,
     * }
     *
     * @throws InvalidArgumentException When FNR resolution fails (same messages as {@see ResolveRakeForRrImportPreview})
     */
    public function handle(User $user, UploadedFile $pdf): array
    {
        $parsed = $this->parser->parse($pdf);
        $fnrRaw = $parsed['fnr'] ?? null;
        $normalizedFnr = is_string($fnrRaw) ? mb_trim($fnrRaw) : '';

        $resolved = $this->resolveRakeForRrImportPreview->handle($normalizedFnr);
        $rake = $resolved['rake'];
        $indent = $resolved['indent'];

        $rake->loadMissing('siding');
        $siding = $rake->siding;

        $sidingIds = $user->isSuperAdmin()
            ? Siding::query()->pluck('id')->all()
            : $user->accessibleSidings()->get()->pluck('id')->all();
        $normalizedSidingIds = array_map(static fn (mixed $id): int => (int) $id, $sidingIds);

        if ($rake->siding_id !== null && ! in_array((int) $rake->siding_id, $normalizedSidingIds, true)) {
            abort(403);
        }

        $this->rrImportService->assertDefaultUploadSlotAvailableForPreview($rake);

        return [
            'fnr_from_rr' => $normalizedFnr,
            'fnr_from_indent' => $indent->fnr_number,
            'to_station_code' => $parsed['to_station_code'] ?? null,
            'rake_destination_code' => $rake->destination_code,
            'rake_destination' => $rake->destination,
            'siding_code' => $siding?->code,
            'siding_name' => $siding?->name,
            'rake_id' => $rake->id,
            'rake_number' => $rake->rake_number,
            'rake_serial_number' => $rake->rake_serial_number,
        ];
    }
}
