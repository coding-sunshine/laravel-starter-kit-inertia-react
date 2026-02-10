<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\HelpArticle;

final readonly class RateHelpArticleAction
{
    public function handle(HelpArticle $article, bool $isHelpful): void
    {
        if ($isHelpful) {
            $article->increment('helpful_count');
        } else {
            $article->increment('not_helpful_count');
        }
    }
}
