<?php

declare(strict_types=1);

namespace App\Console\Commands\Fleet;

use App\Jobs\Ai\ChunkAndEmbedMediaJob;
use Illuminate\Console\Command;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

final class ChunkAndEmbedMediaCommand extends Command
{
    protected $signature = 'fleet:chunk-embed-media
                            {media_id : The Spatie Media model ID to chunk and embed}
                            {--org= : Optional organization ID (otherwise taken from media model)}';

    protected $description = 'Re-index a single media file: extract text, chunk, and embed into document_chunks for RAG.';

    public function handle(): int
    {
        $mediaId = (int) $this->argument('media_id');
        $media = Media::query()->find($mediaId);
        if (! $media) {
            $this->error("Media id {$mediaId} not found.");

            return self::FAILURE;
        }

        $orgId = $this->option('org') ? (int) $this->option('org') : null;
        if (! $orgId && $media->model && isset($media->model->organization_id)) {
            $orgId = (int) $media->model->organization_id;
        }
        if (! $orgId) {
            $this->warn('No organization_id; job may skip indexing. Pass --org=ID to set.');
        }

        dispatch(new ChunkAndEmbedMediaJob($mediaId, $orgId));
        $this->info("Dispatched ChunkAndEmbedMediaJob for media {$mediaId}.");

        return self::SUCCESS;
    }
}
