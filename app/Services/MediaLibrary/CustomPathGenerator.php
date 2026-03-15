<?php

declare(strict_types=1);

namespace App\Services\MediaLibrary;

use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Spatie\MediaLibrary\Support\PathGenerator\PathGenerator;

/**
 * Replicates the v3 path structure so existing S3 files are found.
 *
 * Path: media/{model_table}/{collection}/{media_id}/
 * Example: media/projects/photo/100004/Photo-4.jpg
 */
final class CustomPathGenerator implements PathGenerator
{
    public function getPath(Media $media): string
    {
        return $this->getBasePath($media);
    }

    public function getPathForConversions(Media $media): string
    {
        return $this->getBasePath($media) . 'conversions/';
    }

    public function getPathForResponsiveImages(Media $media): string
    {
        return $this->getBasePath($media) . 'responsive/';
    }

    private function getBasePath(Media $media): string
    {
        $modelTable = $media->model()->getRelated()->getTable();
        $mediaKey = $media->legacy_media_id ?? $media->getKey();

        if ($media->collection_name) {
            return "{$media->getTable()}/{$modelTable}/{$media->collection_name}/{$mediaKey}/";
        }

        return "{$media->getTable()}/{$modelTable}/{$mediaKey}/";
    }
}
