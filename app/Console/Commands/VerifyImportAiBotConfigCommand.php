<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

final class VerifyImportAiBotConfigCommand extends Command
{
    protected $signature = 'fusion:verify-import-ai-bot-config
                            {--min-categories=0 : Minimum expected AI bot categories}
                            {--min-bots=0 : Minimum expected AI bots}
                            {--min-prompts=0 : Minimum expected AI bot prompts}';

    protected $description = 'Verify the AI bot config import was successful.';

    public function handle(): int
    {
        $this->info('Verifying AI bot config import...');

        $minCategories = (int) $this->option('min-categories');
        $minBots = (int) $this->option('min-bots');
        $minPrompts = (int) $this->option('min-prompts');

        $passed = true;

        $categories = DB::table('ai_bot_categories')->count();
        $bots = DB::table('ai_bots')->count();
        $prompts = DB::table('ai_bot_prompts')->count();

        $this->line("  AI bot categories:  {$categories}".($minCategories > 0 ? " (expected >= {$minCategories})" : ''));
        $this->line("  AI bots:            {$bots}".($minBots > 0 ? " (expected >= {$minBots})" : ''));
        $this->line("  AI bot prompts:     {$prompts}".($minPrompts > 0 ? " (expected >= {$minPrompts})" : ''));

        if ($minCategories > 0 && $categories < $minCategories) {
            $this->error("FAIL: Expected at least {$minCategories} categories, found {$categories}");
            $passed = false;
        }

        if ($minBots > 0 && $bots < $minBots) {
            $this->error("FAIL: Expected at least {$minBots} bots, found {$bots}");
            $passed = false;
        }

        if ($minPrompts > 0 && $prompts < $minPrompts) {
            $this->error("FAIL: Expected at least {$minPrompts} prompts, found {$prompts}");
            $passed = false;
        }

        if ($passed) {
            $this->info('PASS: AI bot config import verified.');

            return self::SUCCESS;
        }

        return self::FAILURE;
    }
}
