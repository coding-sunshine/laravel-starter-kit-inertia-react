<?php

declare(strict_types=1);

namespace App\Ai\Agents;

use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Promptable;

/**
 * Agent used to extract text from document/photo images (vision OCR).
 */
final class DocumentImageOcrAgent implements Agent
{
    use Promptable;

    public function instructions(): string
    {
        return 'You are a document OCR assistant. Given an image of a document or photo, extract ALL text visible in the image exactly as written, preserving line breaks and structure where possible. '
            .'If the image contains no text or is not a document, respond with exactly: NONE. '
            .'Reply with only the extracted text (or NONE), no commentary or explanation.';
    }
}
