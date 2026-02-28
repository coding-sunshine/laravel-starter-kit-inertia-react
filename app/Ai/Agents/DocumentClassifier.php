<?php

declare(strict_types=1);

namespace App\Ai\Agents;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\HasStructuredOutput;
use Laravel\Ai\Promptable;

/**
 * Classifies a document snippet into a source type for RAG (mot, v5c, insurance, etc.).
 */
final class DocumentClassifier implements Agent, HasStructuredOutput
{
    use Promptable;

    public const SOURCE_TYPES = ['mot', 'v5c', 'insurance', 'service_history', 'other'];

    public function instructions(): string
    {
        return 'You classify fleet document snippets. Given text from a document, choose the single best type: mot (MOT certificate), v5c (V5C logbook), insurance (insurance policy/certificate), service_history (service or maintenance record), or other. '
            .'Respond with the type and a confidence score between 0 and 1.';
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'source_type' => $schema->string()->enum(self::SOURCE_TYPES)->required()->description('One of: mot, v5c, insurance, service_history, other'),
            'confidence' => $schema->number()->min(0)->max(1)->required()->description('Confidence score between 0 and 1'),
        ];
    }
}
