<?php

declare(strict_types=1);

namespace App\Http\Controllers\Search;

use App\Actions\Search\SearchForCommandPaletteAction;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final readonly class CommandPaletteSearchController
{
    public function __invoke(Request $request, SearchForCommandPaletteAction $action): JsonResponse
    {
        $validated = $request->validate([
            'q' => ['nullable', 'string', 'max:128'],
        ]);

        $results = $action->handle((string) ($validated['q'] ?? ''));

        return response()->json([
            'rakes' => $results->rakes,
            'indents' => $results->indents,
            'rrs' => $results->rrs,
        ]);
    }
}
