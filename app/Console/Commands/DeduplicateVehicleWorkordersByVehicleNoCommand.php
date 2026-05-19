<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Actions\DeduplicateVehicleWorkordersByVehicleNoAction;
use Illuminate\Console\Command;
use Throwable;

final class DeduplicateVehicleWorkordersByVehicleNoCommand extends Command
{
    protected $signature = 'vehicle-workorders:dedupe-by-vehicle-no
                            {--dry-run : List duplicate plates and how many extra rows would be deleted; no database writes}';

    protected $description = 'Remove duplicate vehicle_workorders rows per vehicle_no, keeping the newest id (run before unique migration).';

    public function handle(DeduplicateVehicleWorkordersByVehicleNoAction $action): int
    {
        try {
            $stats = $action->handle((bool) $this->option('dry-run'));
        } catch (Throwable $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        if ($stats['duplicate_plate_groups'] === 0) {
            $this->info('No duplicate non-null vehicle_no values found.');

            return self::SUCCESS;
        }

        if ($this->option('dry-run')) {
            $this->warn('Dry run — no rows were deleted.');
            $this->info(sprintf(
                'Found %d plate(s) with duplicates; %d row(s) would be deleted (newest id kept per plate).',
                $stats['duplicate_plate_groups'],
                $stats['rows_removed'],
            ));

            if (isset($stats['plates']) && $stats['plates'] !== []) {
                $this->newLine();
                $rows = [];
                $plates = $stats['plates'];
                uksort($plates, static fn (string $a, string $b): int => $a <=> $b);

                foreach ($plates as $vehicleNo => $totalRows) {
                    $rows[] = [
                        $vehicleNo,
                        (string) $totalRows,
                        (string) max(0, $totalRows - 1),
                    ];
                }

                $this->table(['vehicle_no', 'rows (total)', 'would delete'], $rows);
            }

            return self::SUCCESS;
        }

        $this->info(sprintf(
            'Finished — %d duplicate plate group(s); %d row(s) deleted (newest id kept per plate).',
            $stats['duplicate_plate_groups'],
            $stats['rows_removed'],
        ));
        $this->comment('You can now run: php artisan migrate');

        return self::SUCCESS;
    }
}
