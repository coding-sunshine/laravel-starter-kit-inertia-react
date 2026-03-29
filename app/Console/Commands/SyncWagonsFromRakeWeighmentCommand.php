<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Rake;
use App\Services\RakeWeighmentPdfImporter;
use Illuminate\Console\Command;

final class SyncWagonsFromRakeWeighmentCommand extends Command
{
    protected $signature = 'rake:sync-wagons-from-weighment
                            {rake : Rake ID}
                            {--weighment= : Rake weighment ID (default: latest PDF weighment)}';

    protected $description = 'Copy wagon number, sequence, type, tare, and PCC from rake_wagon_weighments onto wagons';

    public function handle(RakeWeighmentPdfImporter $importer): int
    {
        $rakeId = (int) $this->argument('rake');
        $rake = Rake::query()->find($rakeId);
        if ($rake === null) {
            $this->error("Rake {$rakeId} not found.");

            return self::FAILURE;
        }

        $weighmentId = $this->option('weighment');
        if ($weighmentId !== null && $weighmentId !== '') {
            $weighment = $rake->rakeWeighments()->whereKey((int) $weighmentId)->first();
        } else {
            $weighment = $rake->rakeWeighments()
                ->whereNotNull('pdf_file_path')
                ->orderByDesc('attempt_no')
                ->orderByDesc('id')
                ->first();
        }

        if ($weighment === null) {
            $this->error('No rake weighment found for this rake.');

            return self::FAILURE;
        }

        $importer->syncWagonsFromRakeWeighment($weighment);

        $this->info("Synced wagons from rake weighment #{$weighment->id}.");

        return self::SUCCESS;
    }
}
