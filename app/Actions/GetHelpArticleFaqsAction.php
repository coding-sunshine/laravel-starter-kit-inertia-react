<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\HelpArticle;
use App\Services\PrismService;
use Illuminate\Support\Facades\Cache;
use Throwable;

final readonly class GetHelpArticleFaqsAction
{
    private const int CACHE_TTL = 86400; // 24 hours

    public function __construct(
        private PrismService $prism,
    ) {}

    /**
     * @return array<int, string>
     */
    public function handle(HelpArticle $article): array
    {
        $cacheKey = "help_article_faqs:{$article->id}";

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($article): array {
            if (! $this->prism->isAvailable()) {
                return [];
            }

            $content = strip_tags((string) $article->content);
            if (mb_strlen($content) < 30) {
                return [];
            }

            $prompt = sprintf(
                "Generate exactly 3 to 5 short questions that readers of this help article might also ask. One question per line, no numbering. Questions only.\n\nTitle: %s\n\nContent excerpt:\n%s",
                $article->title,
                mb_substr($content, 0, 2000),
            );

            try {
                $response = $this->prism->generate($prompt);
                $text = mb_trim($response->text());
                $lines = array_filter(array_map(trim(...), explode("\n", $text)));
                $questions = [];
                foreach ($lines as $line) {
                    $line = preg_replace('/^\d+[.)]\s*/', '', $line);
                    if ($line !== '' && count($questions) < 5) {
                        $questions[] = $line;
                    }
                }

                return array_slice($questions, 0, 5);
            } catch (Throwable) {
                return [];
            }
        });
    }
}
