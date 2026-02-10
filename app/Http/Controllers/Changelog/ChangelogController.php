<?php

declare(strict_types=1);

namespace App\Http\Controllers\Changelog;

use App\Models\ChangelogEntry;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final class ChangelogController
{
    public function index(Request $request): Response
    {
        $entries = ChangelogEntry::query()
            ->published()
            ->with('tags')
            ->latest('released_at')
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('changelog/index', [
            'entries' => $entries,
        ]);
    }
}
