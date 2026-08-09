<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\RrDocument;
use Illuminate\Support\Facades\Storage;
use InvalidArgumentException;

/**
 * Copies an RR PDF from Spatie media on {@see RrDocument} (`rr_pdf` collection) to the application `public` disk under `weighment-pdfs/`.
 */
final readonly class DuplicateRrPdfToWeighmentStorage
{
    public function handle(RrDocument $document): string
    {
        $media = $document->getFirstMedia('rr_pdf');

        if ($media === null) {
            throw new InvalidArgumentException(
                'This RR document has no PDF attached. Upload the RR PDF before fetching weighment data.',
            );
        }

        $disk = Storage::disk('public');
        $relativeDir = 'weighment-pdfs';
        $rakeId = $document->rake_id ?? 0;
        $filename = sprintf(
            'rr-%d-%d-%s.pdf',
            $document->getKey(),
            $rakeId,
            Str::random(20),
        );
        $relativePath = $relativeDir.'/'.$filename;

        $absoluteSource = $media->getPath();
        if ($absoluteSource === '' || ! is_readable($absoluteSource)) {
            throw new InvalidArgumentException(
                'The RR PDF file could not be read from storage.',
            );
        }

        $stream = fopen($absoluteSource, 'rb');
        if ($stream === false) {
            throw new InvalidArgumentException(
                'The RR PDF file could not be opened for reading.',
            );
        }

        try {
            $disk->writeStream($relativePath, $stream);
        } finally {
            if (is_resource($stream)) {
                fclose($stream);
            }
        }

        return $relativePath;
    }
}
