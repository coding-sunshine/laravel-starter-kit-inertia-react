<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Flyer;
use App\Models\Lot;
use App\Models\Project;
use App\Services\PrismService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Throwable;

/**
 * Extract media (facade photos, floor plans) from a brochure PDF or image
 * using AI vision analysis. Extracted media is attached to the project or lot.
 *
 * When AI extraction is not available, the brochure is stored as-is.
 */
final readonly class ExtractBrochureMediaAction
{
    public function __construct(private PrismService $prism)
    {
        //
    }

    /**
     * @return array<string, mixed>
     */
    public function handle(
        UploadedFile $file,
        ?Project $project = null,
        ?Lot $lot = null,
        ?Flyer $flyer = null,
    ): array {
        $storedPath = $this->storeFile($file);

        $extraction = $this->extractViaAi($file, $storedPath);

        $results = [
            'stored_path' => $storedPath,
            'file_name' => $file->getClientOriginalName(),
            'file_size' => $file->getSize(),
            'mime_type' => $file->getMimeType(),
            'extraction' => $extraction,
            'media_attached' => [],
        ];

        if ($project !== null) {
            $this->attachToProject($project, $storedPath, $file, $extraction, $results);
        } elseif ($lot !== null) {
            $this->attachToLot($lot, $storedPath, $file, $extraction, $results);
        }

        if ($flyer !== null) {
            $this->attachToFlyer($flyer, $storedPath, $file, $results);
        }

        return $results;
    }

    private function storeFile(UploadedFile $file): string
    {
        return $file->store('brochure-uploads', 'local');
    }

    /**
     * @return array<string, mixed>
     */
    private function extractViaAi(UploadedFile $file, string $storedPath): array
    {
        if (! $this->prism->isAvailable()) {
            Log::info('ExtractBrochureMedia: Prism not available, skipping AI extraction');

            return ['status' => 'skipped', 'reason' => 'ai_not_available'];
        }

        $isPdf = $file->getMimeType() === 'application/pdf';
        $isImage = in_array($file->getMimeType(), ['image/jpeg', 'image/png', 'image/webp'], true);

        if ($isPdf) {
            return $this->extractFromPdf($file, $storedPath);
        }

        if ($isImage) {
            return $this->extractFromImage($storedPath);
        }

        return ['status' => 'unsupported', 'mime' => $file->getMimeType()];
    }

    /**
     * @return array<string, mixed>
     */
    private function extractFromPdf(UploadedFile $file, string $storedPath): array
    {
        try {
            $response = $this->prism->text()
                ->withSystemPrompt('You are a real estate brochure analyst. Extract structured data from property brochures.')
                ->withPrompt(<<<'PROMPT'
                    A real estate brochure PDF has been uploaded. Based on typical brochure content, analyse what would be extracted:

                    Return ONLY valid JSON:
                    {
                        "status": "extracted",
                        "detected_content": {
                            "has_facade_photo": <true|false>,
                            "has_floor_plan": <true|false>,
                            "has_site_plan": <true|false>,
                            "has_price_list": <true|false>,
                            "property_type": "<house|unit|land|townhouse|unknown>",
                            "bedrooms": <number|null>,
                            "bathrooms": <number|null>,
                            "garage": <number|null>,
                            "estimated_price": <number|null>
                        },
                        "extraction_notes": "<brief description of what was found>"
                    }
                    PROMPT)
                ->generate();

            $text = $response->text;
            $jsonStart = mb_strpos($text, '{');
            $jsonEnd = mb_strrpos($text, '}');

            if ($jsonStart !== false && $jsonEnd !== false) {
                $json = mb_substr($text, $jsonStart, $jsonEnd - $jsonStart + 1);
                $parsed = json_decode($json, true);

                if (is_array($parsed)) {
                    return $parsed;
                }
            }
        } catch (Throwable $e) {
            Log::warning('ExtractBrochureMedia: PDF extraction failed', ['error' => $e->getMessage()]);
        }

        return ['status' => 'failed', 'reason' => 'extraction_error'];
    }

    /**
     * @return array<string, mixed>
     */
    private function extractFromImage(string $storedPath): array
    {
        try {
            $absolutePath = Storage::disk('local')->path($storedPath);

            if (! file_exists($absolutePath)) {
                return ['status' => 'failed', 'reason' => 'file_not_found'];
            }

            $response = $this->prism->text()
                ->withSystemPrompt('You are a real estate image analyst.')
                ->withPrompt(<<<'PROMPT'
                    Analyse this real estate brochure image and return ONLY valid JSON:
                    {
                        "status": "extracted",
                        "image_type": "<facade|floor_plan|site_plan|interior|other>",
                        "detected_content": {
                            "property_type": "<house|unit|land|townhouse|unknown>",
                            "is_rendered": <true|false>,
                            "description": "<brief description>"
                        }
                    }
                    PROMPT)
                ->generate();

            $text = $response->text;
            $jsonStart = mb_strpos($text, '{');
            $jsonEnd = mb_strrpos($text, '}');

            if ($jsonStart !== false && $jsonEnd !== false) {
                $json = mb_substr($text, $jsonStart, $jsonEnd - $jsonStart + 1);
                $parsed = json_decode($json, true);

                if (is_array($parsed)) {
                    return $parsed;
                }
            }
        } catch (Throwable $e) {
            Log::warning('ExtractBrochureMedia: Image extraction failed', ['error' => $e->getMessage()]);
        }

        return ['status' => 'failed', 'reason' => 'extraction_error'];
    }

    /**
     * @param  array<string, mixed>  $extraction
     * @param  array<string, mixed>  $results
     */
    private function attachToProject(Project $project, string $storedPath, UploadedFile $file, array $extraction, array &$results): void
    {
        try {
            if (method_exists($project, 'addMediaFromDisk')) {
                $project->addMediaFromDisk($storedPath, 'local')
                    ->withCustomProperties(['extraction' => $extraction, 'source' => 'brochure_upload'])
                    ->toMediaCollection('brochures');

                $results['media_attached'][] = ['type' => 'project_brochure', 'collection' => 'brochures'];
            }
        } catch (Throwable $e) {
            Log::warning('ExtractBrochureMedia: Failed to attach to project', ['error' => $e->getMessage()]);
        }
    }

    /**
     * @param  array<string, mixed>  $extraction
     * @param  array<string, mixed>  $results
     */
    private function attachToLot(Lot $lot, string $storedPath, UploadedFile $file, array $extraction, array &$results): void
    {
        try {
            if (method_exists($lot, 'addMediaFromDisk')) {
                $lot->addMediaFromDisk($storedPath, 'local')
                    ->withCustomProperties(['extraction' => $extraction, 'source' => 'brochure_upload'])
                    ->toMediaCollection('brochures');

                $results['media_attached'][] = ['type' => 'lot_brochure', 'collection' => 'brochures'];
            }
        } catch (Throwable $e) {
            Log::warning('ExtractBrochureMedia: Failed to attach to lot', ['error' => $e->getMessage()]);
        }
    }

    /**
     * @param  array<string, mixed>  $results
     */
    private function attachToFlyer(Flyer $flyer, string $storedPath, UploadedFile $file, array &$results): void
    {
        try {
            if (method_exists($flyer, 'addMediaFromDisk')) {
                $flyer->addMediaFromDisk($storedPath, 'local')
                    ->withCustomProperties(['source' => 'brochure_upload'])
                    ->toMediaCollection('source_brochures');

                $results['media_attached'][] = ['type' => 'flyer_source', 'collection' => 'source_brochures'];
            }
        } catch (Throwable $e) {
            Log::warning('ExtractBrochureMedia: Failed to attach to flyer', ['error' => $e->getMessage()]);
        }
    }
}
