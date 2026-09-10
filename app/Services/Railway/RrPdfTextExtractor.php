<?php

declare(strict_types=1);

namespace App\Services\Railway;

use App\Services\Railway\Contracts\RrPdfTextExtractorContract;
use InvalidArgumentException;
use Spatie\PdfToText\Exceptions\CouldNotExtractText;
use Spatie\PdfToText\Pdf;

/**
 * Extracts raw text from a PDF file on disk, using the same mechanism
 * {@see RrParserService::parse()} uses (pdftotext -layout). Kept as its own
 * class so callers that already have a file path (e.g. a re-parse command)
 * can reuse the extraction step without needing an UploadedFile.
 */
final class RrPdfTextExtractor implements RrPdfTextExtractorContract
{
    public function extract(string $path): string
    {
        try {
            return Pdf::getText($path, null, ['-layout']);
        } catch (CouldNotExtractText $e) {
            throw new InvalidArgumentException('Could not extract text from PDF.', 0, $e);
        }
    }
}
