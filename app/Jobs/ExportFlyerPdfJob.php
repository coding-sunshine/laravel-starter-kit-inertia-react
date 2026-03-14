<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Events\FlyerExported;
use App\Models\Flyer;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Spatie\LaravelPdf\Facades\Pdf;
use Throwable;

final class ExportFlyerPdfJob implements ShouldQueue
{
    use Queueable;

    public function __construct(public readonly Flyer $flyer) {}

    public function handle(): void
    {
        try {
            $pdfPath = storage_path("app/public/flyers/flyer-{$this->flyer->id}.pdf");

            Pdf::view('flyers.pdf', ['flyer' => $this->flyer])
                ->save($pdfPath);

            $this->flyer->update([
                'pdf_path' => "flyers/flyer-{$this->flyer->id}.pdf",
            ]);

            FlyerExported::dispatch($this->flyer);
        } catch (Throwable $e) {
            Log::error("Flyer PDF export failed for flyer #{$this->flyer->id}: {$e->getMessage()}");
            throw $e;
        }
    }
}
