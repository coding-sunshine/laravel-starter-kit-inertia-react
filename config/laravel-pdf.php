<?php

declare(strict_types=1);

return [
    /*
     * The driver to use for PDF generation.
     * Supported: "browsershot", "dompdf", "cloudflare"
     */
    'driver' => env('LARAVEL_PDF_DRIVER', 'browsershot'),

    /*
     * Here you can configure default Browsershot settings that will be applied
     * to all PDF generation. These settings can still be overridden using the
     * withBrowsershot() method on individual PDF instances.
     */
    'browsershot' => [
        /*
         * Configure the paths to Node.js, npm, Chrome, and other binaries.
         * Leave null to use system defaults or Browsershot's auto-detection.
         */
        'node_binary' => env('LARAVEL_PDF_NODE_BINARY'),
        'npm_binary' => env('LARAVEL_PDF_NPM_BINARY'),
        'include_path' => env('LARAVEL_PDF_INCLUDE_PATH'),
        'chrome_path' => env('LARAVEL_PDF_CHROME_PATH'),
        'node_modules_path' => env('LARAVEL_PDF_NODE_MODULES_PATH'),
        'bin_path' => env('LARAVEL_PDF_BIN_PATH'),
        'temp_path' => env('LARAVEL_PDF_TEMP_PATH'),

        /*
         * Other Browsershot configuration options.
         */
        'write_options_to_file' => env('LARAVEL_PDF_WRITE_OPTIONS_TO_FILE', false),
        'no_sandbox' => env('LARAVEL_PDF_NO_SANDBOX', false),
    ],

    /*
     * Cloudflare Browser Rendering driver configuration.
     */
    'cloudflare' => [
        'api_token' => env('CLOUDFLARE_API_TOKEN'),
        'account_id' => env('CLOUDFLARE_ACCOUNT_ID'),
    ],

    /*
     * DOMPDF driver configuration.
     */
    'dompdf' => [
        'is_remote_enabled' => env('LARAVEL_PDF_DOMPDF_REMOTE_ENABLED', false),
        'chroot' => env('LARAVEL_PDF_DOMPDF_CHROOT'),
    ],
];
