<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Lot;
use App\Models\Project;
use App\Services\PrismService;
use Throwable;

/**
 * Generate AI-powered landing page copy from project/lot listing data using Prism.
 */
final readonly class GenerateLandingPageCopyAction
{
    public function __construct(private PrismService $prism)
    {
        //
    }

    /**
     * @return array<string, string>
     */
    public function handle(Project|Lot $listing): array
    {
        $listingData = $this->extractListingData($listing);
        $prompt = $this->buildPrompt($listingData);

        $responseText = '';

        if ($this->prism->isAvailable()) {
            try {
                $response = $this->prism->text()->withPrompt($prompt)->generate();
                $responseText = $response->text;
            } catch (Throwable) {
                $responseText = '';
            }
        }

        if (empty($responseText)) {
            return $this->fallbackCopy($listingData);
        }

        return $this->parseSections($responseText, $listingData);
    }

    /**
     * @return array<string, string>
     */
    private function extractListingData(Project|Lot $listing): array
    {
        if ($listing instanceof Project) {
            return [
                'type' => 'project',
                'name' => $listing->name,
                'suburb' => $listing->suburb?->name ?? '',
                'state' => $listing->state?->name ?? '',
                'description' => $listing->description ?? '',
                'price_from' => $listing->price_from ? '$'.number_format((int) $listing->price_from) : '',
                'total_lots' => (string) ($listing->lots()->count()),
            ];
        }

        return [
            'type' => 'lot',
            'name' => $listing->lot_number ?? 'Lot '.$listing->id,
            'suburb' => $listing->suburb?->name ?? '',
            'state' => '',
            'description' => $listing->description ?? '',
            'price' => $listing->price ? '$'.number_format((int) $listing->price) : '',
            'bedrooms' => (string) ($listing->bedrooms ?? ''),
            'bathrooms' => (string) ($listing->bathrooms ?? ''),
        ];
    }

    private function buildPrompt(array $data): string
    {
        $dataStr = collect($data)->map(fn ($v, $k) => "{$k}: {$v}")->implode("\n");

        return <<<PROMPT
        You are a real estate marketing copywriter. Generate compelling landing page copy for this listing.

        Listing Details:
        {$dataStr}

        Generate the following sections:
        HEADLINE: <compelling headline, max 10 words>
        SUBHEADLINE: <supporting subheadline, max 20 words>
        HERO_COPY: <hero section paragraph, 2-3 sentences>
        FEATURES: <3-5 key selling points, one per line>
        CTA: <primary call-to-action button text>
        SEO_DESCRIPTION: <meta description, 150-160 characters>
        PROMPT;
    }

    /**
     * @param  array<string, string>  $data
     * @return array<string, string>
     */
    private function parseSections(string $text, array $data): array
    {
        $sections = [];
        $keys = ['HEADLINE', 'SUBHEADLINE', 'HERO_COPY', 'FEATURES', 'CTA', 'SEO_DESCRIPTION'];

        foreach ($keys as $key) {
            if (preg_match("/{$key}:\s*(.+?)(?=\n[A-Z_]+:|$)/si", $text, $m)) {
                $sections[mb_strtolower($key)] = mb_trim($m[1]);
            }
        }

        // Fallback missing sections
        if (empty($sections['headline'])) {
            $sections['headline'] = "Discover {$data['name']} in {$data['suburb']}";
        }

        return $sections;
    }

    /**
     * @param  array<string, string>  $data
     * @return array<string, string>
     */
    private function fallbackCopy(array $data): array
    {
        $name = $data['name'];
        $suburb = $data['suburb'];
        $price = $data['price'] ?? $data['price_from'] ?? '';

        return [
            'headline' => "Your Dream Home Awaits in {$suburb}",
            'subheadline' => "Discover {$name} — premium real estate with modern living.",
            'hero_copy' => "Experience the perfect blend of lifestyle and location at {$name}. Situated in the heart of {$suburb}, this is your opportunity to secure a premium property in a sought-after location.",
            'features' => "• Prime {$suburb} location\n• Modern architectural design\n• Quality finishes throughout\n• Close to schools, shops and transport",
            'cta' => 'Register Your Interest',
            'seo_description' => "Explore {$name} in {$suburb}. {$price}. Register your interest today for exclusive updates.",
        ];
    }
}
