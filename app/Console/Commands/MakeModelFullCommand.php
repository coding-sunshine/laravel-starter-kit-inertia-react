<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Enums\SeederCategory;
use App\Services\RelationshipAnalyzer;
use App\Services\SeedSpecGenerator;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

final class MakeModelFullCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'make:model:full
                            {name : The name of the model}
                            {--category=development : Seeder category (essential, development, production)}
                            {--migration : Create a new migration file for the model}
                            {--factory : Create a new factory for the model}
                            {--seed : Create a new seeder for the model}
                            {--controller : Create a new controller for the model}
                            {--resource : Indicates if the generated controller should be a resource controller}
                            {--api : Indicates if the generated controller should be an API resource controller}
                            {--requests : Create Form Request classes for the model}
                            {--policy : Create a new policy for the model}
                            {--all : Generate a migration, factory, seeder, and resource controller}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new Eloquent model with factory, seeder, and JSON data file';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $name = $this->argument('name');
        $category = $this->getCategory();
        $all = $this->option('all');

        $this->info("Creating full model setup for: {$name}");

        // Create model with migration
        $this->createModel($name, $all);

        // Create factory
        if ($all || $this->option('factory')) {
            $this->createFactory($name);
        }

        // Create seeder
        if ($all || $this->option('seed')) {
            $this->createSeeder($name, $category);
        }

        // Create JSON data file
        $this->createJsonDataFile($name);

        // Generate seed spec
        $this->generateSeedSpec($name);

        // Update manifest
        $this->updateManifest($name, $category);

        // Create controller if requested
        if ($all || $this->option('controller')) {
            $this->createController($name, $all);
        }

        // Create policy if requested
        if ($all || $this->option('policy')) {
            $this->createPolicy($name);
        }

        // Create requests if requested
        if ($all || $this->option('requests')) {
            $this->createRequests($name);
        }

        $this->info("Model setup complete for: {$name}");

        return self::SUCCESS;
    }

    /**
     * Create the model.
     */
    private function createModel(string $name, bool $all): void
    {
        $options = ['--no-interaction' => true];

        if ($all || $this->option('migration')) {
            $options['--migration'] = true;
        }

        Artisan::call('make:model', array_merge(['name' => $name], $options));
        $this->info('✓ Model created');
    }

    /**
     * Create the factory.
     */
    private function createFactory(string $name): void
    {
        Artisan::call('make:factory', [
            'name' => "{$name}Factory",
            '--model' => $name,
            '--no-interaction' => true,
        ]);
        $this->info('✓ Factory created');
    }

    /**
     * Create the seeder.
     */
    private function createSeeder(string $name, SeederCategory $category): void
    {
        $seederName = "{$name}Seeder";
        $categoryPath = database_path("seeders/{$category->value}");

        // Ensure category directory exists
        if (! File::isDirectory($categoryPath)) {
            File::makeDirectory($categoryPath, 0755, true);
        }

        // Create seeder file directly (Laravel's make:seeder doesn't support subdirectories)
        $seederPath = "{$categoryPath}/{$seederName}.php";

        if (File::exists($seederPath)) {
            $this->warn("Seeder already exists: {$seederPath}");
        } else {
            // Create the seeder file with our patterns
            $this->updateSeederFile($name, $seederName, $category);
        }

        $this->info("✓ Seeder created in {$category->value} category");
    }

    /**
     * Update seeder file with our patterns.
     */
    private function updateSeederFile(string $modelName, string $seederName, SeederCategory $category): void
    {
        $seederPath = database_path("seeders/{$category->value}/{$seederName}.php");
        $modelClass = "App\\Models\\{$modelName}";
        $namespace = "Database\\Seeders\\{$category->value}";
        $jsonFileName = Str::snake(Str::plural($modelName)).'.json';

        // Analyze relationships
        $analyzer = new RelationshipAnalyzer();
        $migrationPath = $analyzer->getLatestMigrationForModel($modelName);
        $relationships = $migrationPath ? $analyzer->analyzeMigration($migrationPath) : [];
        $relationshipCode = $analyzer->generateRelationshipSeederCode($relationships, $modelName);

        $content = <<<PHP
<?php

declare(strict_types=1);

namespace {$namespace};

use {$modelClass};
use Database\Seeders\Concerns\LoadsJsonData;
use Illuminate\Database\Seeder;

final class {$seederName} extends Seeder
{
    use LoadsJsonData;

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        \$this->seedRelationships();
        \$this->seedFromJson();
        \$this->seedFromFactory();
    }
{$relationshipCode}
    /**
     * Seed from JSON data file.
     */
    private function seedFromJson(): void
    {
        try {
            \$data = \$this->loadJson('{$jsonFileName}');

            if (! isset(\$data['{$this->getJsonKey($modelName)}']) || ! is_array(\$data['{$this->getJsonKey($modelName)}'])) {
                return;
            }

            foreach (\$data['{$this->getJsonKey($modelName)}'] as \$itemData) {
                \$factoryState = \$itemData['_factory_state'] ?? null;
                unset(\$itemData['_factory_state']);

                \$factory = {$modelName}::factory();

                if (\$factoryState !== null && method_exists(\$factory, \$factoryState)) {
                    \$factory = \$factory->{\$factoryState}();
                }

                \$factory->create(\$itemData);
            }
        } catch (\RuntimeException \$e) {
            // JSON file doesn't exist or is invalid - skip silently
        }
    }

    /**
     * Seed using factory.
     */
    private function seedFromFactory(): void
    {
        {$modelName}::factory()
            ->count(10)
            ->create();
    }
}

PHP;

        File::put($seederPath, $content);
    }

    /**
     * Create JSON data file.
     */
    private function createJsonDataFile(string $modelName): void
    {
        $jsonKey = $this->getJsonKey($modelName);
        $jsonPath = database_path('seeders/data/'.Str::snake(Str::plural($modelName)).'.json');

        $content = [
            $jsonKey => [
                [
                    'id' => 1,
                    // Add example fields here based on model
                ],
            ],
        ];

        File::put($jsonPath, json_encode($content, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        $this->info('✓ JSON data file created');
    }

    /**
     * Update manifest.json.
     */
    private function updateManifest(string $modelName, SeederCategory $category): void
    {
        $manifestPath = database_path('seeders/manifest.json');
        $jsonFileName = Str::snake(Str::plural($modelName)).'.json';

        if (! File::exists($manifestPath)) {
            $manifest = [
                'seeders' => [],
                'categories' => [],
            ];
        } else {
            $manifest = json_decode(File::get($manifestPath), true);
        }

        $seederEntry = [
            'name' => "{$modelName}Seeder",
            'category' => $category->value,
            'description' => "Seeds {$modelName} data",
            'dependencies' => [],
            'data_files' => [$jsonFileName],
        ];

        $manifest['seeders'][] = $seederEntry;

        File::put($manifestPath, json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        $this->info('✓ Manifest updated');
    }

    /**
     * Create controller.
     */
    private function createController(string $name, bool $all): void
    {
        $options = ['--no-interaction' => true];

        if ($all || $this->option('resource')) {
            $options['--resource'] = true;
        }

        if ($all || $this->option('api')) {
            $options['--api'] = true;
        }

        Artisan::call('make:controller', array_merge(['name' => "{$name}Controller"], $options));
        $this->info('✓ Controller created');
    }

    /**
     * Create policy.
     */
    private function createPolicy(string $name): void
    {
        Artisan::call('make:policy', [
            'name' => "{$name}Policy",
            '--model' => $name,
            '--no-interaction' => true,
        ]);
        $this->info('✓ Policy created');
    }

    /**
     * Create form requests.
     */
    private function createRequests(string $name): void
    {
        Artisan::call('make:request', [
            'name' => "Store{$name}Request",
            '--no-interaction' => true,
        ]);

        Artisan::call('make:request', [
            'name' => "Update{$name}Request",
            '--no-interaction' => true,
        ]);

        $this->info('✓ Form requests created');
    }

    /**
     * Get category from option.
     */
    private function getCategory(): SeederCategory
    {
        $category = $this->option('category') ?? 'development';

        return match (mb_strtolower($category)) {
            'essential' => SeederCategory::Essential,
            'production' => SeederCategory::Production,
            default => SeederCategory::Development,
        };
    }

    /**
     * Get JSON key for model name.
     */
    private function getJsonKey(string $modelName): string
    {
        return Str::snake(Str::plural($modelName));
    }

    /**
     * Generate seed spec for model.
     */
    private function generateSeedSpec(string $name): void
    {
        $modelClass = "App\\Models\\{$name}";

        if (! class_exists($modelClass)) {
            return;
        }

        try {
            $generator = app(SeedSpecGenerator::class);
            $spec = $generator->generateSpec($modelClass);
            $generator->saveSpec($modelClass, $spec);
            $this->info('✓ Seed spec created');
        } catch (Exception $e) {
            $this->warn("Could not generate seed spec: {$e->getMessage()}");
        }
    }
}
