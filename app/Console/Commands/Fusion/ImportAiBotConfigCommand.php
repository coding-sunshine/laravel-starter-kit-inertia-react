<?php

declare(strict_types=1);

namespace App\Console\Commands\Fusion;

use App\Models\AiBotBox;
use App\Models\AiBotCategory;
use App\Models\AiBotPromptCommand;
use App\Models\Organization;
use App\Models\Scopes\OrganizationScope;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Throwable;

final class ImportAiBotConfigCommand extends Command
{
    protected $signature = 'fusion:import-ai-bot-config
                            {--force : Run even if legacy connection fails}
                            {--fresh : Truncate ai_bot_* tables first}
                            {--organization-id= : Organization ID to assign (default: first org)}';

    protected $description = 'Import ai_bot_categories, ai_bot_prompt_commands, ai_bot_boxes from MySQL legacy (Step 6).';

    private ?int $organizationId = null;

    /** @var array<int, int> legacy category id => new category id */
    private array $categoryIdMap = [];

    public function handle(): int
    {
        $connection = 'mysql_legacy';

        try {
            DB::connection($connection)->getPdo();
        } catch (Throwable $e) {
            $this->error('Legacy MySQL connection failed: '.$e->getMessage());
            if (! $this->option('force')) {
                return self::FAILURE;
            }
        }

        $this->organizationId = $this->option('organization-id') !== null
            ? (int) $this->option('organization-id')
            : Organization::query()->min('id');

        if ($this->organizationId === null) {
            $this->error('No organization found. Create one or pass --organization-id=.');

            return self::FAILURE;
        }

        if ($this->option('fresh')) {
            $this->truncateTables();
        }

        $this->importCategories($connection);
        $this->importPromptCommands($connection);
        $this->importBoxes($connection);

        $this->info('AI bot config import complete.');

        return self::SUCCESS;
    }

    private function truncateTables(): void
    {
        $this->info('Truncating ai_bot_* tables...');
        AiBotBox::withoutGlobalScope(OrganizationScope::class)->query()->delete();
        AiBotPromptCommand::withoutGlobalScope(OrganizationScope::class)->query()->delete();
        AiBotCategory::withoutGlobalScope(OrganizationScope::class)->query()->delete();
    }

    private function importCategories(string $connection): void
    {
        $this->info('Importing ai_bot_categories...');

        $rows = DB::connection($connection)->table('ai_bot_categories')->orderBy('id')->get();

        foreach ($rows as $row) {
            $legacyId = (int) $row->id;
            $name = $this->string($row, 'name', 'Category');
            $slug = $this->string($row, 'slug') ?? Str::slug($name);
            $orderColumn = $this->int($row, 'order_column') ?? $this->int($row, 'order');

            $cat = AiBotCategory::withoutGlobalScope(OrganizationScope::class)->create([
                'organization_id' => $this->organizationId,
                'name' => $name,
                'slug' => $slug,
                'order_column' => $orderColumn,
            ]);
            $this->categoryIdMap[$legacyId] = $cat->id;
        }
    }

    private function importPromptCommands(string $connection): void
    {
        $this->info('Importing ai_bot_prompt_commands...');

        $rows = DB::connection($connection)->table('ai_bot_prompt_commands')->orderBy('id')->get();

        foreach ($rows as $row) {
            $categoryId = $this->mapCategoryId($row);

            AiBotPromptCommand::withoutGlobalScope(OrganizationScope::class)->create([
                'organization_id' => $this->organizationId,
                'ai_bot_category_id' => $categoryId,
                'name' => $this->string($row, 'name', 'Command'),
                'slug' => $this->string($row, 'slug'),
                'prompt' => $this->string($row, 'prompt'),
                'description' => $this->string($row, 'description'),
                'type' => $this->string($row, 'type'),
                'is_active' => $this->bool($row, 'is_active', true),
                'order_column' => $this->int($row, 'order_column') ?? $this->int($row, 'order'),
            ]);
        }
    }

    private function importBoxes(string $connection): void
    {
        $this->info('Importing ai_bot_boxes...');

        $rows = DB::connection($connection)->table('ai_bot_boxes')->orderBy('id')->get();

        foreach ($rows as $row) {
            $categoryId = $this->mapCategoryId($row);

            AiBotBox::withoutGlobalScope(OrganizationScope::class)->create([
                'organization_id' => $this->organizationId,
                'ai_bot_category_id' => $categoryId,
                'title' => $this->string($row, 'title', 'Box'),
                'description' => $this->string($row, 'description'),
                'page_overview' => $this->string($row, 'page_overview'),
                'type' => $this->string($row, 'type'),
                'visibility' => $this->string($row, 'visibility'),
                'status' => $this->string($row, 'status'),
                'order_column' => $this->int($row, 'order_column') ?? $this->int($row, 'order'),
            ]);
        }
    }

    private function mapCategoryId(object $row): ?int
    {
        $legacyId = isset($row->ai_bot_category_id) ? (int) $row->ai_bot_category_id : null;
        if ($legacyId === null && isset($row->category_id)) {
            $legacyId = (int) $row->category_id;
        }
        if ($legacyId === null) {
            return null;
        }

        return $this->categoryIdMap[$legacyId] ?? null;
    }

    private function string(object $row, string $key, ?string $default = null): ?string
    {
        if (! isset($row->{$key})) {
            return $default;
        }
        $v = $row->{$key};
        if ($v === null || $v === '') {
            return $default;
        }

        return (string) $v;
    }

    private function int(object $row, string $key): ?int
    {
        if (! isset($row->{$key})) {
            return null;
        }
        $v = $row->{$key};
        if ($v === null || $v === '') {
            return null;
        }

        return (int) $v;
    }

    private function bool(object $row, string $key, bool $default = true): bool
    {
        if (! isset($row->{$key})) {
            return $default;
        }
        $v = $row->{$key};

        return filter_var($v, FILTER_VALIDATE_BOOLEAN);
    }
}
