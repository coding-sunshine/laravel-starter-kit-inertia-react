<?php

declare(strict_types=1);

namespace App\Services\Ai;

use App\Ai\Agents\DocumentImageOcrAgent;
use Illuminate\Support\Str;
use Laravel\Ai\Files\Image;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Throwable;

/**
 * Extracts plain text from Spatie Media files for RAG chunking.
 * Supports: text/plain, PDF, DOCX, and images (via vision OCR).
 */
final class DocumentTextExtractor
{
    public function extract(Media $media): string
    {
        $path = $media->getPath();
        if (! $path || ! is_readable($path)) {
            return '';
        }

        $mime = $media->mime_type ?? '';
        $extension = mb_strtolower($media->extension ?? '');

        if (Str::contains($mime, 'text/plain') || $extension === 'txt') {
            return $this->extractTextPlain($path);
        }

        if ($mime === 'application/pdf' || $extension === 'pdf') {
            return $this->extractPdf($path);
        }

        if ($mime === 'application/vnd.openxmlformats-officedocument.wordprocessingml.document' || $extension === 'docx') {
            return $this->extractDocx($path);
        }

        if (Str::startsWith($mime, 'image/')) {
            return $this->extractImageOcr($path);
        }

        return '';
    }

    private function extractTextPlain(string $path): string
    {
        $content = @file_get_contents($path);
        if ($content === false) {
            return '';
        }

        return $content;
    }

    private function extractPdf(string $path): string
    {
        if (! class_exists(\Smalot\PdfParser\Parser::class)) {
            return '';
        }
        try {
            $parser = new \Smalot\PdfParser\Parser;
            $pdf = $parser->parseFile($path);

            return $pdf->getText();
        } catch (Throwable) {
            return '';
        }
    }

    private function extractDocx(string $path): string
    {
        if (! class_exists(\PhpOffice\PhpWord\IOFactory::class)) {
            return '';
        }
        try {
            $phpWord = \PhpOffice\PhpWord\IOFactory::load($path);
            $text = '';
            foreach ($phpWord->getSections() as $section) {
                $elements = method_exists($section, 'getElements') ? $section->getElements() : [];
                foreach ($elements as $element) {
                    $text .= $this->getTextFromPhpWordElement($element);
                }
            }

            return $text;
        } catch (Throwable) {
            return '';
        }
    }

    /**
     * @param  object  $element  PhpOffice\PhpWord\Element\AbstractElement
     */
    private function getTextFromPhpWordElement(object $element): string
    {
        if (method_exists($element, 'getText')) {
            return (string) $element->getText();
        }
        if (method_exists($element, 'getElements')) {
            $out = '';
            foreach ($element->getElements() as $child) {
                $out .= $this->getTextFromPhpWordElement($child);
            }

            return $out;
        }

        return '';
    }

    private function extractImageOcr(string $path): string
    {
        try {
            $agent = resolve(DocumentImageOcrAgent::class);
            $response = $agent->prompt(
                'Extract all text from this document or photo. Return only the raw text, or NONE if there is no text.',
                [Image::fromPath($path)]
            );
            $text = mb_trim((string) $response->text);
            if (mb_strtoupper($text) === 'NONE' || $text === '') {
                return '';
            }

            return $text;
        } catch (Throwable) {
            return '';
        }
    }
}
