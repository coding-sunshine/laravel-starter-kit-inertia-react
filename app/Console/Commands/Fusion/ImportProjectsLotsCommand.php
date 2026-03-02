<?php

declare(strict_types=1);

namespace App\Console\Commands\Fusion;

use App\Models\Developer;
use App\Models\User;
use App\Models\Flyer;
use App\Models\FlyerTemplate;
use App\Models\Lot;
use App\Models\PotentialProperty;
use App\Models\Project;
use App\Models\ProjectUpdate;
use App\Models\Projecttype;
use App\Models\Scopes\OrganizationScope;
use App\Models\SprRequest;
use App\Models\State;
use App\Models\Suburb;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Throwable;

final class ImportProjectsLotsCommand extends Command
{
    protected $signature = 'fusion:import-projects-lots
                            {--force : Run even if legacy connection fails}
                            {--fresh : Truncate all project/lot tables first}
                            {--chunk=200 : Chunk size for projects and lots (smaller = less memory)}
                            {--organization-id= : Organization ID to assign (default: null)}
                            {--skip-events : Disable model events (faster, no activity log for imported rows)}';

    protected $description = 'Import projects, lots, developers, projecttypes, states, suburbs, potential_properties, project_updates, spr_requests, flyers, flyer_templates from MySQL legacy.';

    private ?int $organizationId = null;

    /** @var array<int, int> */
    private array $stateIdMap = [];

    /** @var array<int, int> */
    private array $suburbIdMap = [];

    /** @var array<int, int> */
    private array $projecttypeIdMap = [];

    /** @var array<int, int> */
    private array $developerIdMap = [];

    /** @var array<int, int> */
    private array $projectIdMap = [];

    /** @var array<int, int> */
    private array $lotIdMap = [];

    /** @var array<int, int> */
    private array $flyerTemplateIdMap = [];

    /** @var array<int, true> existing user ids in new app (for FK safety when users not yet imported) */
    private array $existingUserIds = [];

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
            : null;

        $this->existingUserIds = User::query()->pluck('id')->flip()->all();

        if (class_exists(\Laravel\Telescope\Telescope::class)) {
            \Laravel\Telescope\Telescope::stopRecording();
            $this->line('<comment>Telescope recording paused for this run to reduce memory.</comment>');
        }
        $this->line('<comment>If the command hangs or runs out of memory, run: php -d memory_limit=512M artisan fusion:import-projects-lots --chunk=200 --skip-events</comment>');
        $this->newLine();

        if ($this->option('fresh')) {
            $this->truncateTables();
        }

        $this->importStates($connection);
        $this->importSuburbs($connection);
        $this->stateIdMap = [];
        $this->suburbIdMap = [];
        $this->importProjecttypes($connection);
        $this->importDevelopers($connection);
        $this->importProjects($connection);
        $this->importLots($connection);
        $this->importPotentialProperties($connection);
        $this->importProjectUpdates($connection);
        $this->importSprRequests($connection);
        $this->importFlyerTemplates($connection);
        $this->importFlyers($connection);

        $this->info('Import complete. Projects='.Project::withoutGlobalScope(OrganizationScope::class)->count().', Lots='.Lot::count());

        return self::SUCCESS;
    }

    private function truncateTables(): void
    {
        $this->info('Truncating project/lot tables...');
        DB::table('flyers')->delete();
        DB::table('spr_requests')->delete();
        DB::table('project_updates')->delete();
        DB::table('potential_properties')->delete();
        DB::table('lots')->delete();
        DB::table('projects')->delete();
        DB::table('flyer_templates')->delete();
        DB::table('developers')->delete();
        DB::table('projecttypes')->delete();
        DB::table('suburbs')->delete();
        DB::table('states')->delete();
        $this->stateIdMap = [];
        $this->suburbIdMap = [];
        $this->projecttypeIdMap = [];
        $this->developerIdMap = [];
        $this->projectIdMap = [];
        $this->lotIdMap = [];
        $this->flyerTemplateIdMap = [];
    }

    private function importStates(string $connection): void
    {
        $this->info('Importing states...');
        $rows = DB::connection($connection)->table('states')->orderBy('id')->get();
        foreach ($rows as $row) {
            $s = State::withoutGlobalScope(OrganizationScope::class)->firstOrCreate(
                ['legacy_state_id' => (int) $row->id],
                [
                    'organization_id' => $this->organizationId,
                    'short_name' => $row->short_name,
                    'long_name' => $row->long_name,
                ]
            );
            $this->stateIdMap[(int) $row->id] = $s->id;
        }
        $this->line('  States: '.count($this->stateIdMap));
    }

    private function importSuburbs(string $connection): void
    {
        $this->info('Importing suburbs...');
        $total = (int) DB::connection($connection)->table('suburbs')->count();
        $bar = $this->output->createProgressBar($total);
        $bar->setFormat(' %current%/%max% [%bar%] %percent:3s%%');
        $bar->start();
        $lastId = 0;
        $chunkSize = 1000;
        do {
            $rows = DB::connection($connection)->table('suburbs')
                ->where('id', '>', $lastId)
                ->orderBy('id')
                ->limit($chunkSize)
                ->get();
            if ($rows->isEmpty()) {
                break;
            }
            foreach ($rows as $row) {
                Suburb::withoutGlobalScope(OrganizationScope::class)->firstOrCreate(
                    ['legacy_suburb_id' => (int) $row->id],
                    [
                        'organization_id' => $this->organizationId,
                        'postcode' => $row->postcode,
                        'suburb' => $row->suburb,
                        'state' => $row->state,
                        'latitude' => (float) $row->latitude,
                        'longitude' => (float) $row->longitude,
                        'au_town_id' => (int) $row->au_town_id,
                    ]
                );
                $lastId = (int) $row->id;
                $bar->advance();
            }
            $rows = null;
            if (function_exists('gc_collect_cycles')) {
                gc_collect_cycles();
            }
        } while (true);
        $bar->finish();
        $this->newLine();
        $this->line('  Suburbs: '.$total);
    }

    private function importProjecttypes(string $connection): void
    {
        $this->info('Importing projecttypes...');
        $rows = DB::connection($connection)->table('projecttypes')->whereNull('deleted_at')->orderBy('id')->get();
        foreach ($rows as $row) {
            $p = Projecttype::withoutGlobalScope(OrganizationScope::class)->firstOrCreate(
                ['legacy_projecttype_id' => (int) $row->id],
                [
                    'organization_id' => $this->organizationId,
                    'title' => $row->title,
                ]
            );
            $this->projecttypeIdMap[(int) $row->id] = $p->id;
        }
        $this->line('  Projecttypes: '.count($this->projecttypeIdMap));
    }

    private function importDevelopers(string $connection): void
    {
        $this->info('Importing developers...');
        $rows = DB::connection($connection)->table('developers')->whereNull('deleted_at')->orderBy('id')->get();
        foreach ($rows as $row) {
            $userId = $row->user_id ? (int) $row->user_id : null;
            if ($userId !== null && !isset($this->existingUserIds[$userId])) {
                $userId = null; // User not yet imported (Step 2); avoid FK violation
            }
            $d = Developer::withoutGlobalScope(OrganizationScope::class)->firstOrCreate(
                ['legacy_developer_id' => (int) $row->id],
                [
                    'organization_id' => $this->organizationId,
                    'user_id' => $userId,
                    'is_onboard' => (bool) $row->is_onboard,
                    'relationship_status' => $row->relationship_status,
                    'login_info' => $this->decodeJson($row->login_info),
                    'information_delivery' => $row->information_delivery,
                    'commission_note' => $row->commission_note,
                    'build_time' => $row->build_time,
                    'is_active' => (bool) $row->is_active,
                    'extra_attributes' => $this->decodeJson($row->extra_attributes),
                ]
            );
            $this->developerIdMap[(int) $row->id] = $d->id;
        }
        $this->line('  Developers: '.count($this->developerIdMap));
    }

    private function importProjects(string $connection): void
    {
        $this->info('Importing projects...');
        $chunkSize = (int) $this->option('chunk');
        $total = (int) DB::connection($connection)->table('projects')->count();
        $bar = $this->output->createProgressBar($total);
        $bar->setFormat(' %current%/%max% [%bar%] %percent:3s%%');
        $bar->start();
        $skipEvents = $this->option('skip-events');

        DB::connection($connection)->table('projects')->orderBy('id')->chunkById($chunkSize, function ($rows) use ($bar, $skipEvents): void {
            foreach ($rows as $row) {
                $doImport = function () use ($row) {
                    return Project::withoutGlobalScope(OrganizationScope::class)->updateOrCreate(
                    ['legacy_project_id' => (int) $row->id],
                    [
                        'organization_id' => $this->organizationId,
                        'title' => $row->title ?? '',
                        'stage' => $row->stage,
                        'estate' => $row->estate ?? '',
                        'total_lots' => $row->total_lots,
                        'storeys' => $row->storeys,
                        'min_landsize' => $row->min_landsize,
                        'max_landsize' => $row->max_landsize,
                        'min_living_area' => $row->min_living_area,
                        'max_living_area' => $row->max_living_area,
                        'bedrooms' => $row->bedrooms,
                        'bathrooms' => $row->bathrooms,
                        'min_bedrooms' => $row->min_bedrooms,
                        'max_bedrooms' => $row->max_bedrooms,
                        'min_bathrooms' => $row->min_bathrooms,
                        'max_bathrooms' => $row->max_bathrooms,
                        'garage' => $row->garage,
                        'min_rent' => $row->min_rent,
                        'max_rent' => $row->max_rent,
                        'avg_rent' => $row->avg_rent,
                        'min_rent_yield' => $row->min_rent_yield,
                        'max_rent_yield' => $row->max_rent_yield,
                        'avg_rent_yield' => $row->avg_rent_yield,
                        'rent_to_sell_yield' => $row->rent_to_sell_yield ?? null,
                        'is_hot_property' => (bool) $row->is_hot_property,
                        'description' => $row->description,
                        'min_price' => $row->min_price,
                        'max_price' => $row->max_price,
                        'avg_price' => $row->avg_price,
                        'body_corporate_fees' => $row->body_corporate_fees,
                        'min_body_corporate_fees' => $row->min_body_corporate_fees ?? null,
                        'max_body_corporate_fees' => $row->max_body_corporate_fees ?? null,
                        'rates_fees' => $row->rates_fees,
                        'min_rates_fees' => $row->min_rates_fees ?? null,
                        'max_rates_fees' => $row->max_rates_fees ?? null,
                        'sub_agent_comms' => $row->sub_agent_comms ?? null,
                        'is_archived' => (bool) $row->is_archived,
                        'is_hidden' => (bool) $row->is_hidden,
                        'start_at' => $row->start_at,
                        'end_at' => $row->end_at,
                        'is_smsf' => (bool) ($row->is_smsf ?? true),
                        'is_firb' => (bool) ($row->is_firb ?? true),
                        'is_ndis' => (bool) ($row->is_ndis ?? true),
                        'is_cashflow_positive' => (bool) ($row->is_cashflow_positive ?? true),
                        'build_time' => $row->build_time,
                        'historical_growth' => $row->historical_growth,
                        'land_info' => $row->land_info,
                        'developer_id' => $row->developer_id ? ($this->developerIdMap[(int) $row->developer_id] ?? null) : null,
                        'projecttype_id' => $row->projecttype_id ? ($this->projecttypeIdMap[(int) $row->projecttype_id] ?? null) : null,
                        'is_featured' => (bool) ($row->is_featured ?? false),
                        'trust_details' => $this->decodeJson($row->trust_details ?? null),
                        'property_conditions' => $row->property_conditions ?? null,
                        'is_co_living' => (bool) ($row->is_co_living ?? false),
                        'is_rooming' => (bool) ($row->is_rooming ?? false),
                        'is_rent_to_sell' => (bool) ($row->is_rent_to_sell ?? false),
                        'is_flexi' => (bool) ($row->is_flexi ?? false),
                        'is_exclusive' => (bool) ($row->is_exclusive ?? false),
                        'created_by' => $this->resolveUserId($row->created_by ?? null),
                        'updated_by' => $this->resolveUserId($row->modified_by ?? null),
                    ]
                );
                };
                $project = $skipEvents ? Model::withoutEvents($doImport) : $doImport();
                $this->projectIdMap[(int) $row->id] = $project->id;
                $bar->advance();
            }
            unset($rows);
            if (function_exists('gc_collect_cycles')) {
                gc_collect_cycles();
            }
        }, 'id', 'id');

        $bar->finish();
        $this->newLine();
        $this->line('  Projects: '.count($this->projectIdMap));
    }

    private function importLots(string $connection): void
    {
        $this->info('Importing lots...');
        $chunkSize = (int) $this->option('chunk');
        $total = (int) DB::connection($connection)->table('lots')->whereNull('deleted_at')->count();
        $bar = $this->output->createProgressBar($total);
        $bar->setFormat(' %current%/%max% [%bar%] %percent:3s%%');
        $bar->start();
        $skipEvents = $this->option('skip-events');

        DB::connection($connection)->table('lots')->whereNull('deleted_at')->orderBy('id')->chunkById($chunkSize, function ($rows) use ($bar, $skipEvents): void {
            foreach ($rows as $row) {
                $newProjectId = $this->projectIdMap[(int) $row->project_id] ?? null;
                if ($newProjectId === null) {
                    $bar->advance();
                    continue;
                }
                $doImport = function () use ($row, $newProjectId) {
                    return Lot::updateOrCreate(
                    ['legacy_lot_id' => (int) $row->id],
                    [
                        'project_id' => $newProjectId,
                        'title' => $row->title,
                        'land_price' => $row->land_price,
                        'build_price' => $row->build_price,
                        'stage' => $row->stage,
                        'level' => $row->level,
                        'building' => $row->building,
                        'floorplan' => $row->floorplan,
                        'car' => $row->car,
                        'storage' => $row->storage,
                        'view' => $row->view,
                        'garage' => $row->garage,
                        'aspect' => $row->aspect,
                        'internal' => $row->internal,
                        'external' => $row->external,
                        'total' => $row->total,
                        'storyes' => $row->storyes,
                        'land_size' => $row->land_size,
                        'title_status' => $row->title_status,
                        'living_area' => $row->living_area,
                        'price' => $row->price,
                        'bedrooms' => $row->bedrooms,
                        'bathrooms' => $row->bathrooms,
                        'study' => $row->study,
                        'mpr' => $row->mpr,
                        'powder_room' => $row->powder_room,
                        'balcony' => $row->balcony,
                        'rent_yield' => $row->rent_yield,
                        'weekly_rent' => $row->weekly_rent,
                        'rent_to_sell_yield' => $row->rent_to_sell_yield ?? null,
                        'rates' => $row->rates,
                        'five_percent_share_price' => $row->five_percent_share_price ?? null,
                        'sub_agent_comms' => $row->sub_agent_comms ?? null,
                        'body_corporation' => $row->body_corporation,
                        'is_archived' => (bool) $row->is_archived,
                        'is_nras' => (bool) $row->is_nras,
                        'is_smsf' => (bool) $row->is_smsf,
                        'is_cashflow_positive' => (bool) $row->is_cashflow_positive,
                        'completion' => $row->completion,
                        'uuid' => $row->uuid ?? null,
                        'created_by' => $this->resolveUserId($row->created_by ?? null),
                        'updated_by' => $this->resolveUserId($row->modified_by ?? null),
                    ]
                );
                };
                $lot = $skipEvents ? Model::withoutEvents($doImport) : $doImport();
                $this->lotIdMap[(int) $row->id] = $lot->id;
                $bar->advance();
            }
            unset($rows);
            if (function_exists('gc_collect_cycles')) {
                gc_collect_cycles();
            }
        }, 'id', 'id');

        $bar->finish();
        $this->newLine();
        $this->line('  Lots: '.count($this->lotIdMap));
    }

    private function importPotentialProperties(string $connection): void
    {
        $this->info('Importing potential_properties...');
        $rows = DB::connection($connection)->table('potential_properties')->orderBy('id')->get();
        $count = 0;
        foreach ($rows as $row) {
            PotentialProperty::withoutGlobalScope(OrganizationScope::class)->firstOrCreate(
                [
                    'organization_id' => $this->organizationId,
                    'title' => $row->title,
                    'developer' => $row->developer ?? '',
                ],
                [
                    'projecttype_id' => $row->projecttype_id ? ($this->projecttypeIdMap[(int) $row->projecttype_id] ?? null) : null,
                ]
            );
            $count++;
        }
        $this->line('  Potential properties: '.$count);
    }

    private function importProjectUpdates(string $connection): void
    {
        $this->info('Importing project_updates...');
        $rows = DB::connection($connection)->table('project_updates')->orderBy('id')->get();
        $count = 0;
        foreach ($rows as $row) {
            $newProjectId = $this->projectIdMap[(int) $row->project_id] ?? null;
            if ($newProjectId === null) {
                continue;
            }
            ProjectUpdate::firstOrCreate(
                [
                    'project_id' => $newProjectId,
                    'user_id' => $row->user_id ? (int) $row->user_id : null,
                    'type' => $row->type,
                    'created_at' => $row->created_at,
                ],
                [
                    'extra_attributes' => $this->decodeJson($row->extra_attributes),
                ]
            );
            $count++;
        }
        $this->line('  Project updates: '.$count);
    }

    private function importSprRequests(string $connection): void
    {
        $this->info('Importing spr_requests...');
        $rows = DB::connection($connection)->table('spr_requests')->whereNull('deleted_at')->orderBy('id')->get();
        $count = 0;
        foreach ($rows as $row) {
            $newProjectId = $this->projectIdMap[(int) $row->project_id] ?? null;
            if ($newProjectId === null) {
                continue;
            }
            SprRequest::firstOrCreate(
                [
                    'user_id' => $this->resolveUserId($row->user_id),
                    'project_id' => $newProjectId,
                    'spr_count' => (int) $row->spr_count,
                    'transaction_access_code' => $row->transaction_access_code,
                    'created_at' => $row->created_at,
                ],
                [
                    'spr_price' => $row->spr_price,
                    'is_payment_completed' => (bool) $row->is_payment_completed,
                    'is_request_completed' => (bool) $row->is_request_completed,
                    'transaction_id' => $row->transaction_id,
                    'extra_attributes' => $this->decodeJson($row->extra_attributes),
                    'created_by' => $this->resolveUserId($row->created_by),
                    'updated_by' => $this->resolveUserId($row->updated_by),
                ]
            );
            $count++;
        }
        $this->line('  SPR requests: '.$count);
    }

    private function importFlyerTemplates(string $connection): void
    {
        $this->info('Importing flyer_templates...');
        $rows = DB::connection($connection)->table('flyer_templates')->orderBy('id')->get();
        foreach ($rows as $row) {
            $t = FlyerTemplate::withoutGlobalScope(OrganizationScope::class)->firstOrCreate(
                ['legacy_flyer_template_id' => (int) $row->id],
                [
                    'organization_id' => $this->organizationId,
                    'template_id' => $row->template_id,
                    'name' => $row->name,
                    'preview_img' => $row->preview_img,
                    'is_enabled' => (bool) ($row->is_enabled ?? true),
                ]
            );
            $this->flyerTemplateIdMap[(int) $row->id] = $t->id;
        }
        $this->line('  Flyer templates: '.count($this->flyerTemplateIdMap));
    }

    private function importFlyers(string $connection): void
    {
        $this->info('Importing flyers...');
        $rows = DB::connection($connection)->table('flyers')->whereNull('deleted_at')->orderBy('id')->get();
        $count = 0;
        foreach ($rows as $row) {
            $newProjectId = $this->projectIdMap[(int) $row->project_id] ?? null;
            $newLotId = $this->lotIdMap[(int) $row->lot_id] ?? null;
            if ($newProjectId === null || $newLotId === null) {
                continue;
            }
            Flyer::firstOrCreate(
                [
                    'template_id' => $row->template_id ? ($this->flyerTemplateIdMap[(int) $row->template_id] ?? null) : null,
                    'project_id' => $newProjectId,
                    'lot_id' => $newLotId,
                    'created_at' => $row->created_at,
                ],
                [
                    'user_id' => $row->user_id ? (int) $row->user_id : null,
                    'page_html' => $row->page_html,
                    'page_css' => $row->page_css,
                    'poster_img_id' => $row->poster_img_id,
                    'floorplan_img_id' => $row->floorplan_img_id,
                    'notes' => $row->notes,
                    'is_custom' => (bool) ($row->is_custom ?? false),
                    'created_by' => $row->created_by ?? null,
                    'updated_by' => $row->modified_by ?? null,
                ]
            );
            $count++;
        }
        $this->line('  Flyers: '.$count);
    }

    private function resolveUserId(mixed $legacyUserId): ?int
    {
        if ($legacyUserId === null || $legacyUserId === '') {
            return null;
        }
        $id = (int) $legacyUserId;
        return isset($this->existingUserIds[$id]) ? $id : null;
    }

    /**
     * @return array<string, mixed>|null
     */
    private function decodeJson(mixed $value): ?array
    {
        if ($value === null || $value === '') {
            return null;
        }
        if (is_array($value)) {
            return $value;
        }
        $decoded = json_decode(is_string($value) ? $value : (string) $value, true);

        return is_array($decoded) ? $decoded : null;
    }
}
