<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Rake;
use Carbon\Carbon;
use Illuminate\Console\Command;

final class PakurBackfillPlacementCommand extends Command
{
    protected $signature = 'pakur:backfill-placement
                            {--file= : Path to CSV with columns rake_number,placed_at,released_at,source}
                            {--force : Overwrite existing placement_time and loading_end_time}';

    protected $description = 'Backfill placement_time and loading_end_time on rakes from a CSV file (used to recover historical Pakur data).';

    public function handle(): int
    {
        $path = (string) $this->option('file');
        if ($path === '' || ! is_file($path)) {
            $this->error('Provide a valid --file path.');

            return self::INVALID;
        }

        $force = (bool) $this->option('force');
        $updated = 0;
        $skipped = 0;
        $handle = fopen($path, 'r');
        $headers = fgetcsv($handle);

        while (($row = fgetcsv($handle)) !== false) {
            $data = array_combine($headers, $row);
            $rake = Rake::query()->where('rake_number', $data['rake_number'])->first();

            if ($rake === null) {
                $skipped++;
                $this->line("Skipped — rake_number {$data['rake_number']} not found");

                continue;
            }

            $changes = [];
            if (! empty($data['placed_at']) && ($force || $rake->placement_time === null)) {
                $changes['placement_time'] = Carbon::parse($data['placed_at']);
            }
            if (! empty($data['released_at']) && ($force || $rake->loading_end_time === null)) {
                $changes['loading_end_time'] = Carbon::parse($data['released_at']);
            }

            if ($changes === []) {
                $skipped++;

                continue;
            }

            $rake->update($changes);
            $updated++;
        }

        fclose($handle);

        $this->info("Updated {$updated} rake(s). Skipped {$skipped} row(s).");

        return self::SUCCESS;
    }
}
