<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Rake;
use Illuminate\Support\Facades\File;

final readonly class RakeWeighmentExcelTemplateResolver
{
    public const string ERROR_NO_SIDING = 'no_siding';

    public const string ERROR_UNKNOWN_SIDING = 'unknown_siding';

    public const string ERROR_FILE_MISSING = 'file_missing';

    /**
     * @return array{absolute_path: string, download_basename: string}|array{error: string}
     */
    public function resolve(Rake $rake): array
    {
        $rake->loadMissing('siding');

        if ($rake->siding_id === null || $rake->siding === null) {
            return ['error' => self::ERROR_NO_SIDING];
        }

        $code = mb_strtoupper(mb_trim((string) ($rake->siding->code ?? '')));
        if ($code === '') {
            return ['error' => self::ERROR_UNKNOWN_SIDING];
        }

        $relativePath = $this->relativePathForSidingCode($code);
        if ($relativePath === null) {
            return ['error' => self::ERROR_UNKNOWN_SIDING];
        }

        $directory = (string) config('rake_weighment.excel_templates_directory', 'rake_weighment_excel_template');
        $absolutePath = public_path($directory.DIRECTORY_SEPARATOR.$relativePath);

        if (! File::isFile($absolutePath)) {
            return ['error' => self::ERROR_FILE_MISSING];
        }

        $downloadBasename = sprintf(
            'weighment-template-%s.xlsx',
            preg_replace('/[^A-Za-z0-9_\-]+/', '-', (string) $rake->rake_number) ?: 'rake'
        );

        return [
            'absolute_path' => $absolutePath,
            'download_basename' => $downloadBasename,
        ];
    }

    private function relativePathForSidingCode(string $upperCode): ?string
    {
        $templates = config('rake_weighment.excel_templates', []);

        foreach ($templates as $variant) {
            $codes = $variant['siding_codes'] ?? [];
            $normalized = array_map(static fn (string $c): string => mb_strtoupper(mb_trim($c)), $codes);
            if (in_array($upperCode, $normalized, true)) {
                return $variant['relative_path'] ?? null;
            }
        }

        return null;
    }
}
