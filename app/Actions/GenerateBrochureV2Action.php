<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\BrochureLayout;
use App\Models\Flyer;
use App\Services\AiCreditService;
use App\Services\PrismService;
use Spatie\LaravelPdf\Facades\Pdf;
use Throwable;

final readonly class GenerateBrochureV2Action
{
    public function __construct(
        private PrismService $prism,
        private AiCreditService $aiCredits,
    ) {
        //
    }

    public function handle(Flyer $flyer, ?BrochureLayout $layout = null): string
    {
        $aiContent = $this->generateAiContent($flyer);

        $outputPath = storage_path("app/brochures/brochure_{$flyer->id}_v2.pdf");

        if (! is_dir(dirname($outputPath))) {
            mkdir(dirname($outputPath), 0755, true);
        }

        $layoutConfig = $layout?->layout_config ?? [];

        Pdf::view('brochures.v2', [
            'flyer' => $flyer,
            'layout' => $layout,
            'layoutConfig' => $layoutConfig,
            'aiContent' => $aiContent,
            'project' => $flyer->project,
            'lot' => $flyer->lot,
        ])
            ->format('A4')
            ->landscape()
            ->save($outputPath);

        return $outputPath;
    }

    /**
     * @return array<string, mixed>
     */
    private function generateAiContent(Flyer $flyer): array
    {
        $user = auth()->user();

        if ($user === null || ! $this->prism->isAvailable() || ! $this->aiCredits->canUse($user, 'generate_brochure_v2')) {
            return [];
        }

        $context = '';

        if ($flyer->project !== null) {
            $context = "Project: {$flyer->project->name}";
        }

        if ($flyer->lot !== null) {
            $context .= " Lot: {$flyer->lot->lot_number}, Price: {$flyer->lot->price}";
        }

        if ($context === '') {
            return [];
        }

        try {
            $response = $this->prism->text()
                ->withSystemPrompt('You are a real estate brochure copywriter.')
                ->withPrompt("Generate brochure content for: {$context}\n\nReturn JSON with: tagline (max 60 chars), description (2-3 sentences), key_features (array of 4 items), call_to_action.")
                ->generate();

            $this->aiCredits->deduct($user, 'generate_brochure_v2');

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
        } catch (Throwable) {
            // fallthrough
        }

        return [];
    }
}
