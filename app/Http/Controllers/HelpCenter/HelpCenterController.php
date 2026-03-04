<?php

declare(strict_types=1);

namespace App\Http\Controllers\HelpCenter;

use App\Actions\GetHelpArticleFaqsAction;
use App\Actions\SummarizeHelpArticleAction;
use App\Models\HelpArticle;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final class HelpCenterController
{
    public function index(Request $request): Response
    {
        $query = $request->query('q', '');
        if ($query !== '') {
            $ids = HelpArticle::search($query)
                ->query(fn ($builder) => $builder->published())
                ->take(50)
                ->get()
                ->pluck('id');
            $featured = HelpArticle::query()
                ->published()
                ->whereIn('id', $ids)
                ->featured()
                ->orderBy('order')
                ->limit(6)
                ->get();
            $byCategory = HelpArticle::query()
                ->published()
                ->whereIn('id', $ids)
                ->orderBy('order')
                ->get()
                ->groupBy('category');
        } else {
            $featured = HelpArticle::query()
                ->published()
                ->featured()
                ->orderBy('order')
                ->limit(6)
                ->get();
            $byCategory = HelpArticle::query()
                ->published()
                ->orderBy('order')
                ->get()
                ->groupBy('category');
        }

        return Inertia::render('help/index', [
            'featured' => $featured,
            'byCategory' => $byCategory->map(fn ($articles) => $articles->values()->all())->all(),
            'searchQuery' => $query,
        ]);
    }

    public function show(HelpArticle $helpArticle, SummarizeHelpArticleAction $summarize, GetHelpArticleFaqsAction $getFaqs): Response
    {
        abort_unless($helpArticle->is_published, 404);

        $helpArticle->increment('views');
        $helpArticle->load('tags');

        $related = HelpArticle::query()
            ->published()
            ->where('id', '!=', $helpArticle->id)
            ->where('category', $helpArticle->category)
            ->orderBy('order')
            ->limit(5)
            ->get();

        $summary = Inertia::defer(fn (): ?string => $summarize->handle($helpArticle));
        $peopleAlsoAsked = Inertia::defer(fn (): array => $getFaqs->handle($helpArticle));

        return Inertia::render('help/show', [
            'article' => $helpArticle,
            'related' => $related,
            'summary' => $summary,
            'peopleAlsoAsked' => $peopleAlsoAsked,
        ]);
    }
}
