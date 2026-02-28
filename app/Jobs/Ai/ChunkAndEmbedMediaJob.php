<?php

declare(strict_types=1);

namespace App\Jobs\Ai;

use App\Ai\Agents\DocumentClassifier;
use App\Ai\Agents\DocumentExtractionAgent;
use App\Events\Ai\DocumentChunksCreated;
use App\Models\Fleet\DocumentChunk;
use App\Models\Scopes\OrganizationScope;
use App\Services\Ai\DocumentTextExtractor;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

final class ChunkAndEmbedMediaJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    private const CHUNK_SIZE = 1500;

    private const CHUNK_OVERLAP = 200;

    /** @var array<string> */
    private static array $sourceTypeHints = ['mot', 'v5c', 'insurance', 'service_history', 'policy', 'certificate'];

    public function __construct(
        public int $mediaId,
        public ?int $organizationId = null,
    ) {}

    public function handle(DocumentTextExtractor $extractor): void
    {
        $media = Media::find($this->mediaId);
        if (! $media) {
            return;
        }

        $model = $media->model;
        $organizationId = $this->organizationId ?? (method_exists($model, 'getAttribute') ? $model?->organization_id : null);
        if (! $organizationId) {
            Log::info('ChunkAndEmbedMediaJob: Skipping media {id}, no organization_id.', ['id' => $this->mediaId]);
            return;
        }

        $text = $extractor->extract($media);
        $text = trim(preg_replace('/\s+/', ' ', $text));
        if ($text === '') {
            Log::debug('ChunkAndEmbedMediaJob: No text extracted from media {id}.', ['id' => $this->mediaId]);
            return;
        }

        $chunks = $this->chunkText($text);
        $sourceType = $this->classifySourceType($media, $chunks);
        $extractedMetadata = $this->extractMetadataIfRelevant($sourceType, $text);

        DocumentChunk::withoutGlobalScope(OrganizationScope::class)
            ->where('chunkable_type', Media::class)
            ->where('chunkable_id', $media->id)
            ->delete();

        $hasVectorColumn = \Illuminate\Support\Facades\Schema::hasColumn((new DocumentChunk)->getTable(), 'embedding_vector');
        $hasTokenCount = \Illuminate\Support\Facades\Schema::hasColumn((new DocumentChunk)->getTable(), 'token_count');
        $created = 0;

        foreach ($chunks as $index => $content) {
            try {
                $embedding = Str::of($content)->toEmbeddings(provider: 'openrouter_embeddings', dimensions: 1536);
            } catch (\Throwable $e) {
                Log::warning('ChunkAndEmbedMediaJob: Embedding failed for chunk.', ['error' => $e->getMessage()]);
                continue;
            }
            if (! is_array($embedding) || empty($embedding)) {
                continue;
            }

            $payload = [
                'organization_id' => $organizationId,
                'chunkable_type' => Media::class,
                'chunkable_id' => $media->id,
                'source_type' => $sourceType,
                'chunk_index' => $index,
                'content' => $content,
            ];
            if ($index === 0 && $extractedMetadata !== null) {
                $payload['metadata'] = $extractedMetadata;
            }
            if ($hasTokenCount) {
                $payload['token_count'] = $this->estimateTokenCount($content);
            }
            if ($hasVectorColumn) {
                $payload['embedding_vector'] = $embedding;
            } else {
                $payload['embedding'] = $embedding;
            }

            DocumentChunk::withoutGlobalScope(OrganizationScope::class)->create($payload);
            $created++;
        }

        if ($created > 0) {
            DocumentChunksCreated::dispatch($media, (int) $organizationId, $created);
        } else {
            Log::warning('ChunkAndEmbedMediaJob: No chunks created for media {id} (embedding failed for all). Restart queue worker so it uses openrouter_embeddings and ensure OPENROUTER_API_KEY is set.', ['id' => $this->mediaId]);
        }
    }

    private function classifySourceType(Media $media, array $chunks): string
    {
        $snippet = $chunks[0] ?? '';
        if (mb_strlen($snippet) > 2000) {
            $snippet = mb_substr($snippet, 0, 2000);
        }
        if ($snippet === '') {
            return $this->inferSourceType($media);
        }
        try {
            $agent = app(DocumentClassifier::class);
            $response = $agent->prompt('Classify this document snippet:'."\n\n".$snippet);
            if (! $response instanceof \Laravel\Ai\Responses\StructuredAgentResponse) {
                return $this->inferSourceType($media);
            }
            $type = $response['source_type'] ?? null;
            $confidence = $response['confidence'] ?? 0;
            if (is_string($type) && $confidence >= 0.3 && in_array(strtolower($type), DocumentClassifier::SOURCE_TYPES, true)) {
                return strtolower($type);
            }
        } catch (\Throwable $e) {
            Log::debug('ChunkAndEmbedMediaJob: Classifier failed, using fallback.', ['error' => $e->getMessage()]);
        }
        return $this->inferSourceType($media);
    }

    private function estimateTokenCount(string $content): int
    {
        $words = str_word_count($content);
        return (int) ceil($words * 1.35);
    }

    /**
     * @return array<string, mixed>|null
     */
    private function extractMetadataIfRelevant(string $sourceType, string $fullText): ?array
    {
        if (! in_array($sourceType, ['mot', 'insurance'], true)) {
            return null;
        }
        $snippet = mb_strlen($fullText) > 4000 ? mb_substr($fullText, 0, 4000) : $fullText;
        try {
            $agent = app(DocumentExtractionAgent::class);
            $response = $agent->prompt('Extract key data from this document:'."\n\n".$snippet);
            if (! $response instanceof \Laravel\Ai\Responses\StructuredAgentResponse) {
                return null;
            }
            $out = [];
            if (! empty($response['expiry_date'])) {
                $out['expiry_date'] = $response['expiry_date'];
            }
            if (! empty($response['certificate_number'])) {
                $out['certificate_number'] = $response['certificate_number'];
            }
            if (! empty($response['document_type'])) {
                $out['document_type'] = $response['document_type'];
            }
            return $out === [] ? null : $out;
        } catch (\Throwable $e) {
            Log::debug('ChunkAndEmbedMediaJob: Extraction failed.', ['error' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * @return array<int, string>
     */
    private function chunkText(string $text): array
    {
        $chunks = [];
        $start = 0;
        $len = strlen($text);
        $index = 0;
        while ($start < $len) {
            $slice = mb_substr($text, $start, self::CHUNK_SIZE);
            if ($slice !== '') {
                $chunks[$index++] = $slice;
            }
            $start += self::CHUNK_SIZE - self::CHUNK_OVERLAP;
        }
        return $chunks;
    }

    private function inferSourceType(Media $media): string
    {
        $name = strtolower($media->name ?? '');
        $fileName = strtolower($media->file_name ?? '');
        $collection = strtolower($media->collection_name ?? '');
        $combined = $name.' '.$fileName.' '.$collection;
        foreach (self::$sourceTypeHints as $hint) {
            if (str_contains($combined, $hint)) {
                return $hint;
            }
        }
        return 'other';
    }
}
