<?php

declare(strict_types=1);

namespace Database\Seeders\Development;

use App\Actions\ImportRakeDataFromExcelAction;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Imports historical rake/penalty/weighment data from PRD Excel files.
 *
 * DISABLED: Import does not store all data nor proper rake numbers. Re-enable
 * when ImportRakeDataFromExcelAction and column mapping are fixed. Until then
 * this seeder is no-op when running db:seed or seed:environment.
 *
 * Reads Excel files under config(rrmcs.prd_import). Skips if base path or
 * files are missing (e.g. in CI). PDFs in the PRD folder are reference-only
 * and are not imported.
 *
 * Run with: php artisan db:seed --class='Database\Seeders\Development\RealDataImportSeeder'
 * Or as part of full seed when PRD docs are present.
 *
 * Visibility: The Rakes index filters by the current user's assigned sidings
 * (see RakeDataTable::tableBaseQuery). Imported rakes are assigned to sidings
 * PKUR, DUMK, and KURWA. To see them, log in as super-admin or ensure your
 * user has at least one of those sidings assigned (siding_user pivot).
 */
final class RealDataImportSeeder extends Seeder
{
    public array $dependencies = [
        'SidingSeeder',
        'UserSeeder',
    ];

    public function run(): void
    {
        $this->command?->info('RealDataImportSeeder disabled (import does not store all data / proper rake numbers).');

        // --- Original implementation (re-enable when import is fixed) ---
        // $basePath = config('rrmcs.prd_import.base_path');
        // if (! $basePath || ! is_dir(base_path($basePath))) {
        //     $this->command?->info('PRD import skipped: base path not found.');
        //     return;
        // }
        // $userId = User::query()->where('email', 'superadmin@rmms.local')->first()?->id ?? User::query()->first()?->id;
        // $result = resolve(ImportRakeDataFromExcelAction::class)->handle($userId);
        // $this->command?->info(sprintf(
        //     'Real data import: %d rakes, %d penalties, %d weighments, %d RR documents, %d wagons.',
        //     $result['rakes'], $result['penalties'], $result['weighments'], $result['rr_documents'], $result['wagons']
        // ));
        // if ($result['skipped_sheets'] > 0) {
        //     $this->command?->info(sprintf('Skipped %d non-data sheets.', $result['skipped_sheets']));
        // }
        // foreach ($result['errors'] as $err) {
        //     $this->command?->warn('Import: '.$err);
        // }
    }
}
