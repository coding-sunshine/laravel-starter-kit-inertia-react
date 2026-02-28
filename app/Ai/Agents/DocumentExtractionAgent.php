<?php

declare(strict_types=1);

namespace App\Ai\Agents;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\HasStructuredOutput;
use Laravel\Ai\Promptable;

/**
 * Extracts structured data (expiry, certificate number) from MOT/insurance document text.
 * Used to populate document_chunks.metadata for compliance/expiry alerts.
 */
final class DocumentExtractionAgent implements Agent, HasStructuredOutput
{
    use Promptable;

    public function instructions(): string
    {
        return 'You extract key facts from fleet documents (MOT, insurance, V5C). Given document text, return any expiry date (Y-m-d), certificate/reference number, and document type. Use null for missing values.';
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'expiry_date' => $schema->string()->nullable()->required()->description('Expiry date in Y-m-d format if found'),
            'certificate_number' => $schema->string()->nullable()->required()->description('Certificate or reference number if found'),
            'document_type' => $schema->string()->nullable()->required()->description('Document type: mot, insurance, v5c, or other'),
        ];
    }
}
