<?php

declare(strict_types=1);

namespace App\Http\Controllers\HelpCenter;

use App\Models\HelpArticle;
use App\Models\Scopes\OrganizationScope;
use Inertia\Inertia;
use Inertia\Response;

final class HelpCenterController
{
    public function index(): Response
    {
        $featured = HelpArticle::query()
            ->withoutGlobalScope(OrganizationScope::class)
            ->published()
            ->featured()
            ->orderBy('order')
            ->limit(6)
            ->get();

        $byCategory = HelpArticle::query()
            ->withoutGlobalScope(OrganizationScope::class)
            ->published()
            ->orderBy('order')
            ->get()
            ->groupBy('category');

        return Inertia::render('help/index', [
            'featured' => $featured,
            'byCategory' => $byCategory->map(fn ($articles) => $articles->values()->all())->all(),
        ]);
    }

    public function show(HelpArticle $helpArticle): Response
    {
        abort_unless($helpArticle->is_published, 404);

        $helpArticle->increment('views');
        $helpArticle->load('tags');

        $related = HelpArticle::query()
            ->withoutGlobalScope(OrganizationScope::class)
            ->published()
            ->where('id', '!=', $helpArticle->id)
            ->where('category', $helpArticle->category)
            ->orderBy('order')
            ->limit(5)
            ->get();

        return Inertia::render('help/show', [
            'article' => $helpArticle,
            'related' => $related,
        ]);
    }
}
