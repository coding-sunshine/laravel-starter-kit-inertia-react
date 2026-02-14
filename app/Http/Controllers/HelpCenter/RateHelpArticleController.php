<?php

declare(strict_types=1);

namespace App\Http\Controllers\HelpCenter;

use App\Actions\RateHelpArticleAction;
use App\Models\HelpArticle;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

final class RateHelpArticleController
{
    public function __invoke(Request $request, HelpArticle $helpArticle): RedirectResponse
    {
        $validated = $request->validate([
            'is_helpful' => ['required', 'boolean'],
        ]);

        resolve(RateHelpArticleAction::class)->handle($helpArticle, (bool) $validated['is_helpful']);

        return back()->with('status', 'Thank you for your feedback.');
    }
}
