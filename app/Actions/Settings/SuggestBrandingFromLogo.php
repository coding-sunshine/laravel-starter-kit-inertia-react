<?php

declare(strict_types=1);

namespace App\Actions\Settings;

use Illuminate\Http\UploadedFile;

/**
 * Suggests theme preset (and optionally primary/background colors) from a logo image.
 * For now returns a default preset; can be extended with color extraction or AI.
 *
 * @return array{theme_preset: string|null, theme_radius?: string, theme_font?: string}
 */
final readonly class SuggestBrandingFromLogo
{
    public function handle(?UploadedFile $logo = null): array
    {
        if (! $logo instanceof UploadedFile || ! $logo->isValid()) {
            $presets = config('theme.org_allowed_presets', ['default', 'fleet', 'vega', 'nova']);
            $first = $presets[0] ?? 'default';

            return ['theme_preset' => $first];
        }

        // TODO: Extract dominant color from image (e.g. via intervention/image or a microservice)
        // and map to nearest preset or return custom primary hex.
        $presets = config('theme.org_allowed_presets', ['default', 'fleet', 'vega', 'nova']);
        $first = $presets[0] ?? 'default';

        return ['theme_preset' => $first];
    }
}
