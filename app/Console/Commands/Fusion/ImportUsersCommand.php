<?php

declare(strict_types=1);

namespace App\Console\Commands\Fusion;

use App\Models\Contact;
use App\Models\Scopes\OrganizationScope;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Throwable;

final class ImportUsersCommand extends Command
{
    protected $signature = 'fusion:import-users
                            {--fresh : Truncate users first (WARNING: destructive)}
                            {--chunk=200 : Chunk size for processing}';

    protected $description = 'Import users from MySQL legacy and link to contacts via lead_id→contact_id map. Idempotent: safe to re-run.';

    /** @var array<int, int> legacy lead id => new contact id */
    private array $leadToContactMap = [];

    public function handle(): int
    {
        $connection = 'mysql_legacy';

        try {
            DB::connection($connection)->getPdo();
        } catch (Throwable $e) {
            $this->error('Legacy MySQL connection failed: '.$e->getMessage());
            $this->line('Set MYSQL_LEGACY_HOST, MYSQL_LEGACY_DATABASE, MYSQL_LEGACY_USERNAME, MYSQL_LEGACY_PASSWORD in .env.');

            return self::FAILURE;
        }

        $this->leadToContactMap = Contact::withoutGlobalScope(OrganizationScope::class)
            ->whereNotNull('legacy_lead_id')
            ->pluck('id', 'legacy_lead_id')
            ->all();

        if ($this->leadToContactMap === []) {
            $this->warn('No contacts with legacy_lead_id found. Run fusion:import-contacts first.');

            return self::FAILURE;
        }

        $this->info('Lead→Contact map loaded: '.count($this->leadToContactMap).' entries.');

        if ($this->option('fresh')) {
            $this->warn('Truncating users table...');
            DB::table('users')->delete();
        }

        $this->importUsers($connection);

        $this->info('Import complete. Users: '.User::count());

        return self::SUCCESS;
    }

    private function importUsers(string $connection): void
    {
        $this->info('Importing users...');
        $chunkSize = (int) $this->option('chunk');
        $total = (int) DB::connection($connection)->table('users')->count();
        $bar = $this->output->createProgressBar($total);
        $bar->start();

        DB::connection($connection)->table('users')
            ->orderBy('id')
            ->chunkById($chunkSize, function ($rows) use ($bar): void {
                foreach ($rows as $row) {
                    $contactId = null;
                    if (isset($row->lead_id) && $row->lead_id !== null) {
                        $contactId = $this->leadToContactMap[(int) $row->lead_id] ?? null;
                    }

                    User::updateOrCreate(
                        ['email' => $row->email],
                        [
                            'name' => $row->name ?? $row->email,
                            'password' => $row->password ?? bcrypt(str()->random(32)),
                            'contact_id' => $contactId,
                            'email_verified_at' => $row->email_verified_at ?? null,
                            'created_at' => $row->created_at,
                            'updated_at' => $row->updated_at,
                        ]
                    );

                    $bar->advance();
                }

                unset($rows);
                if (function_exists('gc_mem_caches')) {
                    gc_mem_caches();
                }
            }, 'id', 'id');

        $bar->finish();
        $this->newLine();
    }
}
