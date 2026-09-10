<?php

declare(strict_types=1);

namespace Database\Seeders\Development;

use App\Models\Indent;
use App\Models\RrDocument;
use App\Models\Weighment;
use Illuminate\Database\Seeder;
use Throwable;

/**
 * Attaches sample PDFs from prd/docs/Rake Management Application - references
 * to existing RrDocument, Weighment, and Indent records so demo data shows
 * real-looking documents (View PDF / View slip / View confirmation).
 *
 * RR: cycles through Kurwa (RAKE-1..5) and Dumka samples so more RRs get a PDF.
 * Weighment: attaches the single sample to up to 5 weighments. Indent: up to 3.
 *
 * Skips silently if files or records are missing. Run after RakeManagementDemoSeeder
 * (and optionally after RealDataImportSeeder) so records exist.
 *
 * Run: php artisan db:seed --class='Database\Seeders\Development\ReferencePdfAttachmentSeeder'
 */
final class ReferencePdfAttachmentSeeder extends Seeder
{
    private const string BASE = 'prd/docs/Rake Management Application - references';

    /** RR sample paths (relative to base_path). Cycled when attaching to multiple RRs. */
    private const array RR_SAMPLES = [
        self::BASE.'/RR Samples (Kurwa)/RAKE-1.pdf',
        self::BASE.'/RR Samples (Kurwa)/RAKE-2.pdf',
        self::BASE.'/RR Samples (Kurwa)/RAKE-3.pdf',
        self::BASE.'/RR Samples (Kurwa)/RAKE-4.pdf',
        self::BASE.'/RR Samples (Kurwa)/RAKE-5.pdf',
        self::BASE.'/RR Samples (Dumka)-RR Nov.-2025/RAKE-8.pdf',
        self::BASE.'/RR Samples (Dumka)-RR Nov.-2025/RAKE-9.pdf',
        self::BASE.'/RR Samples (Dumka)-RR Nov.-2025/RAKE-10.pdf',
        self::BASE.'/RR Samples (Dumka)-RR Nov.-2025/RAKE-11.pdf',
        self::BASE.'/RR Samples (Dumka)-RR Nov.-2025/RAKE-12.pdf',
    ];

    private const string WEIGHMENT_SAMPLE = self::BASE.'/Weighment & Indent/Weighment - RAKE NO-29.pdf';

    private const string INDENT_SAMPLE = self::BASE.'/Weighment & Indent/PN-302 PSPM (Indent).pdf';

    private const int MAX_RR_ATTACHMENTS = 15;

    private const int MAX_WEIGHMENT_ATTACHMENTS = 5;

    private const int MAX_INDENT_ATTACHMENTS = 3;

    public array $dependencies = [
        'RakeManagementDemoSeeder',
    ];

    public function run(): void
    {
        $this->attachRrSamples();
        $this->attachWeighmentSamples();
        $this->attachIndentSamples();
    }

    private function attachRrSamples(): void
    {
        $docs = RrDocument::query()
            ->whereDoesntHave('media', fn ($q) => $q->where('collection_name', 'rr_pdf'))
            ->orderBy('id')
            ->limit(self::MAX_RR_ATTACHMENTS)
            ->get();

        $paths = array_values(array_filter(
            array_map(base_path(...), self::RR_SAMPLES),
            is_file(...)
        ));

        if ($paths === []) {
            return;
        }

        foreach ($docs as $i => $doc) {
            $path = $paths[$i % count($paths)];
            try {
                $doc->addMedia($path)->toMediaCollection('rr_pdf');
            } catch (Throwable) {
                // Skip if media fails (e.g. disk)
            }
        }
    }

    private function attachWeighmentSamples(): void
    {
        $path = base_path(self::WEIGHMENT_SAMPLE);
        if (! is_file($path)) {
            return;
        }

        $weighments = Weighment::query()
            ->whereDoesntHave('media', fn ($q) => $q->where('collection_name', 'weighment_slip_pdf'))
            ->orderBy('id')
            ->limit(self::MAX_WEIGHMENT_ATTACHMENTS)
            ->get();

        foreach ($weighments as $w) {
            try {
                $w->addMedia($path)->toMediaCollection('weighment_slip_pdf');
            } catch (Throwable) {
                //
            }
        }
    }

    private function attachIndentSamples(): void
    {
        $path = base_path(self::INDENT_SAMPLE);
        if (! is_file($path)) {
            return;
        }

        $indents = Indent::query()
            ->whereDoesntHave('media', fn ($q) => $q->where('collection_name', 'indent_confirmation_pdf'))
            ->orderBy('id')
            ->limit(self::MAX_INDENT_ATTACHMENTS)
            ->get();

        foreach ($indents as $indent) {
            try {
                $indent->addMedia($path)->toMediaCollection('indent_confirmation_pdf');
            } catch (Throwable) {
                //
            }
        }
    }
}
