<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\HelpArticle;
use App\Services\PrismService;
use Illuminate\Support\Facades\Cache;
use Throwable;

final readonly class SummarizeHelpArticleAction
{
    private const int CACHE_TTL = 86400; // 24 hours

    public function __construct(
        private PrismService $prism,
    ) {}

    public function handle(HelpArticle $article): ?string
    {
        $cacheKey = "help_article_summary:{$article->id}";

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($article): ?string {
            if (! $this->prism->isAvailable()) {
                return null;
            }

            $content = strip_tags((string) $article->content);
            if (mb_strlen($content) < 50) {
                return $article->excerpt ?? null;
            }

            $prompt = sprintf(
                "Summarize this help article in 2-3 concise sentences. Focus on the key steps or information.\n\nTitle: %s\n\nContent:\n%s",
                $article->title,
                mb_substr($content, 0, 3000),
            );

            try {
                $response = $this->prism->generate($prompt);

                return mb_trim($response->text());
            } catch (Throwable) {
                return null;
            }
        });
    }
}
