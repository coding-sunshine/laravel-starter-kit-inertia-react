<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Rake;
use App\Models\Siding;
use Illuminate\Console\Command;

/**
 * Print a sample of imported rakes so you can verify Excel → DB mapping.
 *
 * Compare the output with the same rows in your PRD Excel files.
 * See docs/developer/backend/import-excel-column-mapping.md for column mapping.
 */
final class ImportSampleCommand extends Command
{
    protected $signature = 'rrmcs:import-sample
                            {--limit=10 : Number of rakes to show}
                            {--siding= : Filter by siding code (e.g. PKUR, DUMK, KURWA)}';

    protected $description = 'Print sample of imported rakes to verify Excel→DB mapping';

    public function handle(): int
    {
        $limit = (int) $this->option('limit');
        $sidingCode = $this->option('siding');

        $query = Rake::query()
            ->with(['siding:id,code,name'])
            ->orderByDesc('id');

        if ($sidingCode !== null && $sidingCode !== '') {
            $siding = Siding::query()->where('code', $sidingCode)->first();
            if ($siding === null) {
                $this->error("Siding with code '{$sidingCode}' not found.");

                return 1;
            }
            $query->where('siding_id', $siding->id);
        }

        $rakes = $query->limit($limit)->get();

        if ($rakes->isEmpty()) {
            $this->warn('No rakes found. Run RealDataImportSeeder first and ensure PRD Excel path exists.');

            return 0;
        }

        $this->info('Sample rakes (compare with Excel by rake_number and dates):');
        $this->newLine();

        $rows = $rakes->map(fn (Rake $r): array => [
            $r->id,
            $r->rake_number,
            $r->siding?->code ?? '-',
            $r->loading_end_time?->format('Y-m-d H:i') ?? '-',
            $r->loading_start_time?->format('Y-m-d H:i') ?? '-',
            $r->loaded_weight_mt !== null ? (string) $r->loaded_weight_mt : '-',
            $r->wagon_count ?? '-',
            $r->state ?? '-',
        ]);

        $this->table(
            ['id', 'rake_number', 'siding', 'loading_end_time', 'loading_start_time', 'loaded_weight_mt', 'wagon_count', 'state'],
            $rows
        );

        $this->newLine();
        $this->comment('Column mapping: docs/developer/backend/import-excel-column-mapping.md');

        return 0;
    }
}
