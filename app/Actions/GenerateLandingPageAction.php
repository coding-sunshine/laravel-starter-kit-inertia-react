<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\LandingPageTemplate;
use App\Services\AiCreditService;
use App\Services\PrismService;
use Illuminate\Support\Str;
use Throwable;

final readonly class GenerateLandingPageAction
{
    public function __construct(
        private PrismService $prism,
        private AiCreditService $aiCredits,
    ) {
        //
    }

    public function handle(string $projectName, string $description, string $targetAudience = 'home buyers'): LandingPageTemplate
    {
        $content = $this->generateContent($projectName, $description, $targetAudience);

        return LandingPageTemplate::create([
            'organization_id' => tenant('id'),
            'name' => "Landing Page: {$projectName}",
            'slug' => Str::slug("{$projectName}-".Str::random(6)),
            'headline' => $content['headline'] ?? "Discover {$projectName}",
            'sub_headline' => $content['sub_headline'] ?? $description,
            'html_content' => $content['html_content'] ?? $this->defaultHtml($projectName, $description),
            'status' => 'draft',
            'meta_title' => $content['meta_title'] ?? $projectName,
            'meta_description' => $content['meta_description'] ?? $description,
        ]);
    }

    /**
     * @return array<string, string>
     */
    private function generateContent(string $projectName, string $description, string $targetAudience): array
    {
        $user = auth()->user();

        if ($user === null || ! $this->prism->isAvailable() || ! $this->aiCredits->canUse($user, 'generate_landing_page')) {
            return [];
        }

        try {
            $response = $this->prism->text()
                ->withSystemPrompt('You are a real estate landing page copywriter.')
                ->withPrompt("Generate landing page content for: {$projectName}\nDescription: {$description}\nTarget audience: {$targetAudience}\n\nReturn JSON with: headline, sub_headline, html_content (full HTML body), meta_title, meta_description.")
                ->generate();

            $this->aiCredits->deduct($user, 'generate_landing_page');

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

    private function defaultHtml(string $projectName, string $description): string
    {
        return <<<HTML
<section class="hero">
  <h1>{$projectName}</h1>
  <p>{$description}</p>
  <a href="#enquire" class="cta-btn">Enquire Now</a>
</section>
<section id="enquire">
  <h2>Register Your Interest</h2>
  <p>Contact us to find out more about {$projectName}.</p>
</section>
HTML;
    }
}
