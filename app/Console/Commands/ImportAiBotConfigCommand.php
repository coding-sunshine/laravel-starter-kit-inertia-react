<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

final class ImportAiBotConfigCommand extends Command
{
    protected $signature = 'fusion:import-ai-bot-config
                            {--dry-run : Preview without writing to DB}
                            {--chunk=500 : Rows per DB transaction chunk}
                            {--force : Re-import existing rows (updateOrCreate)}';

    protected $description = 'Import AI bot categories, bots, and prompt commands from the legacy MySQL database.';

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $chunk = (int) $this->option('chunk');
        $force = (bool) $this->option('force');

        if ($dryRun) {
            $this->info('[DRY RUN] '.$this->description);
        } else {
            $this->info($this->description);
        }

        $failed = 0;
        $failed += $this->importCategories($dryRun, $chunk, $force);
        $failed += $this->importBots($dryRun, $chunk, $force);
        $failed += $this->importPrompts($dryRun, $chunk, $force);

        if ($failed > 0) {
            $this->error("Import completed with {$failed} failures.");

            return self::FAILURE;
        }

        $this->info('AI bot config import completed successfully.');

        return self::SUCCESS;
    }

    private function importCategories(bool $dryRun, int $chunk, bool $force): int
    {
        $this->info('Importing AI bot categories...');
        $failed = 0;

        if (! $this->legacyTableExists('ai_bot_categories')) {
            $this->warn('  Legacy table ai_bot_categories not found — skipping.');

            return 0;
        }

        DB::connection('mysql_legacy')
            ->table('ai_bot_categories')
            ->orderBy('id')
            ->chunk($chunk, function ($rows) use ($dryRun, $force, &$failed) {
                foreach ($rows as $row) {
                    try {
                        $catName = $row->title ?? $row->name ?? 'Category '.$row->id;

                        if ($dryRun) {
                            $this->line("  [DRY] Category: {$catName}");

                            continue;
                        }

                        $data = [
                            'name' => $catName,
                            'slug' => \Illuminate\Support\Str::slug($catName),
                            'description' => $row->sub_title ?? $row->description ?? null,
                            'icon' => $row->icon ?? null,
                            'is_system' => false,
                            'is_active' => ($row->status ?? 1) === 1,
                        ];

                        if ($force) {
                            DB::table('ai_bot_categories')->updateOrInsert(['slug' => $data['slug']], $data);
                        } else {
                            DB::table('ai_bot_categories')->insertOrIgnore($data);
                        }
                    } catch (Throwable $e) {
                        $failed++;
                        Log::warning("fusion:import-ai-bot-config: category {$row->id} failed: {$e->getMessage()}");
                    }
                }
            });

        $count = DB::table('ai_bot_categories')->count();
        $this->info("  → {$count} categories in DB");

        return $failed;
    }

    private function importBots(bool $dryRun, int $chunk, bool $force): int
    {
        $this->info('Importing AI bots...');
        $failed = 0;

        if (! $this->legacyTableExists('ai_bots') && ! $this->legacyTableExists('ai_bot_boxes')) {
            $this->warn('  Legacy AI bot tables not found — skipping.');

            return 0;
        }

        $legacyTable = $this->legacyTableExists('ai_bots') ? 'ai_bots' : 'ai_bot_boxes';

        DB::connection('mysql_legacy')
            ->table($legacyTable)
            ->orderBy('id')
            ->chunk($chunk, function ($rows) use ($dryRun, $force, &$failed) {
                foreach ($rows as $row) {
                    try {
                        $botName = $row->title ?? $row->name ?? 'Bot '.$row->id;

                        if ($dryRun) {
                            $this->line("  [DRY] Bot: {$botName}");

                            continue;
                        }

                        $categoryId = null;
                        if (! empty($row->ai_bot_category_id ?? $row->category_id)) {
                            $legacyCatId = $row->ai_bot_category_id ?? $row->category_id;
                            // Try to match by position/order since IDs may differ
                            $categoryId = DB::table('ai_bot_categories')->skip((int) $legacyCatId - 1)->value('id');
                        }

                        $data = [
                            'category_id' => $categoryId,
                            'name' => $botName,
                            'slug' => \Illuminate\Support\Str::slug($botName).'-'.$row->id,
                            'description' => $row->description ?? $row->page_overview ?? null,
                            'icon' => null,
                            'is_system' => false,
                            'is_active' => ($row->status ?? 1) === 1,
                        ];

                        if ($force) {
                            DB::table('ai_bots')->updateOrInsert(['slug' => $data['slug']], $data);
                        } else {
                            DB::table('ai_bots')->insertOrIgnore($data);
                        }
                    } catch (Throwable $e) {
                        $failed++;
                        Log::warning("fusion:import-ai-bot-config: bot {$row->id} failed: {$e->getMessage()}");
                    }
                }
            });

        $count = DB::table('ai_bots')->count();
        $this->info("  → {$count} bots in DB");

        return $failed;
    }

    private function importPrompts(bool $dryRun, int $chunk, bool $force): int
    {
        $this->info('Importing AI bot prompts...');
        $failed = 0;

        $legacyTable = null;
        foreach (['ai_bot_prompts', 'ai_bot_prompt_commands'] as $t) {
            if ($this->legacyTableExists($t)) {
                $legacyTable = $t;
                break;
            }
        }

        if ($legacyTable === null) {
            $this->warn('  Legacy AI prompt tables not found — skipping.');

            return 0;
        }

        DB::connection('mysql_legacy')
            ->table($legacyTable)
            ->orderBy('id')
            ->chunk($chunk, function ($rows) use ($dryRun, $force, &$failed) {
                foreach ($rows as $row) {
                    try {
                        $promptText = $row->prompt_command ?? $row->prompt ?? $row->name ?? 'Prompt '.$row->id;

                        if ($dryRun) {
                            $this->line('  [DRY] Prompt: '.mb_substr($promptText, 0, 50));

                            continue;
                        }

                        // Map legacy bot_id to new bot
                        $botId = null;
                        $legacyBotId = $row->ai_bot_box_id ?? $row->bot_id ?? null;
                        if ($legacyBotId) {
                            $botId = DB::table('ai_bots')->skip((int) $legacyBotId - 1)->value('id');
                        }

                        $data = [
                            'bot_id' => $botId,
                            'label' => mb_substr($promptText, 0, 255),
                            'prompt_template' => $promptText,
                            'sort_order' => $row->sort_order ?? 0,
                            'created_at' => $row->created_at ?? now(),
                            'updated_at' => $row->updated_at ?? now(),
                        ];

                        if ($force) {
                            DB::table('ai_bot_prompts')->updateOrInsert(
                                ['bot_id' => $botId, 'label' => $data['label']],
                                $data
                            );
                        } else {
                            DB::table('ai_bot_prompts')->insertOrIgnore($data);
                        }
                    } catch (Throwable $e) {
                        $failed++;
                        Log::warning("fusion:import-ai-bot-config: prompt {$row->id} failed: {$e->getMessage()}");
                    }
                }
            });

        $count = DB::table('ai_bot_prompts')->count();
        $this->info("  → {$count} prompts in DB");

        return $failed;
    }

    private function legacyTableExists(string $table): bool
    {
        try {
            return DB::connection('mysql_legacy')
                ->getSchemaBuilder()
                ->hasTable($table);
        } catch (Throwable) {
            return false;
        }
    }
}
