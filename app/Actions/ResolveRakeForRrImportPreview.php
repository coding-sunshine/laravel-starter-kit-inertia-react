<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Indent;
use App\Models\Rake;
use InvalidArgumentException;

final readonly class ResolveRakeForRrImportPreview
{
    /**
     * Resolve the rake linked to an indent by FNR (e-demand).
     *
     * @return array{indent: Indent, rake: Rake}
     */
    public function handle(string $normalizedFnr): array
    {
        if ($normalizedFnr === '') {
            throw new InvalidArgumentException('FNR could not be read from this Railway Receipt PDF.');
        }

        $indent = Indent::query()
            ->where('fnr_number', $normalizedFnr)
            ->with(['rake.siding'])
            ->first();

        if ($indent === null) {
            throw new InvalidArgumentException('No e-demand found for this RR FNR.');
        }

        $rake = $indent->rake;
        if ($rake === null) {
            throw new InvalidArgumentException('No rake is linked to this e-demand.');
        }

        return ['indent' => $indent, 'rake' => $rake];
    }
}
