<?php

declare(strict_types=1);

namespace App\Console\Commands\Fusion;

use App\Models\AiBotBox;
use App\Models\AiBotCategory;
use App\Models\AiBotPromptCommand;
use App\Models\Scopes\OrganizationScope;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Throwable;

final class VerifyImportAiBotConfigCommand extends Command
{
    protected $signature = 'fusion:verify-import-ai-bot-config
                            {--legacy=mysql_legacy : Legacy DB connection to compare counts}';

    protected $description = 'Verify Step 6 AI bot config import: compare ai_bot_categories, ai_bot_prompt_commands, ai_bot_boxes counts to legacy.';

    public function handle(): int
    {
        $connection = $this->option('legacy');

        $expectedCategories = null;
        $expectedCommands = null;
        $expectedBoxes = null;

        try {
            DB::connection($connection)->getPdo();
            $expectedCategories = (int) DB::connection($connection)->table('ai_bot_categories')->count();
            $expectedCommands = (int) DB::connection($connection)->table('ai_bot_prompt_commands')->count();
            $expectedBoxes = (int) DB::connection($connection)->table('ai_bot_boxes')->count();
        } catch (Throwable $e) {
            $this->warn('Could not read legacy counts: '.$e->getMessage());
        }

        $actualCategories = AiBotCategory::withoutGlobalScope(OrganizationScope::class)->count();
        $actualCommands = AiBotPromptCommand::withoutGlobalScope(OrganizationScope::class)->count();
        $actualBoxes = AiBotBox::withoutGlobalScope(OrganizationScope::class)->count();

        $this->table(
            ['Table', 'Expected (legacy)', 'Actual (PostgreSQL)', 'OK'],
            [
                ['ai_bot_categories', $expectedCategories ?? '—', $actualCategories, $this->ok($expectedCategories, $actualCategories) ? '✓' : '✗'],
                ['ai_bot_prompt_commands', $expectedCommands ?? '—', $actualCommands, $this->ok($expectedCommands, $actualCommands) ? '✓' : '✗'],
                ['ai_bot_boxes', $expectedBoxes ?? '—', $actualBoxes, $this->ok($expectedBoxes, $actualBoxes) ? '✓' : '✗'],
            ],
        );

        if ($expectedCategories !== null && $actualCategories !== $expectedCategories) {
            $this->warn('ai_bot_categories count does not match. Re-run fusion:import-ai-bot-config with --fresh if needed.');
        }
        if ($expectedCommands !== null && $actualCommands !== $expectedCommands) {
            $this->warn('ai_bot_prompt_commands count does not match. Re-run fusion:import-ai-bot-config with --fresh if needed.');
        }
        if ($expectedBoxes !== null && $actualBoxes !== $expectedBoxes) {
            $this->warn('ai_bot_boxes count does not match. Re-run fusion:import-ai-bot-config with --fresh if needed.');
        }

        return self::SUCCESS;
    }

    private function ok(?int $expected, int $actual): bool
    {
        if ($expected === null) {
            return true;
        }

        return $actual === $expected;
    }
}
