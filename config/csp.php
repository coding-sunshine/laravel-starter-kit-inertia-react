<?php

declare(strict_types=1);

use Spatie\Csp\Directive;
use Spatie\Csp\Keyword;
use Spatie\Csp\Nonce\RandomString;
use Spatie\Csp\Presets\Basic;

return [

    /*
     * Presets will determine which CSP headers will be set. A valid CSP preset is
     * any class that implements `Spatie\Csp\Preset`
     */
    'presets' => [
        Basic::class,
    ],

    /**
     * Additional global CSP directives for Inertia + Vite compatibility.
     */
    'directives' => array_filter([
        [Directive::STYLE, array_filter([
            Keyword::SELF,
            Keyword::UNSAFE_INLINE,
            'https://fonts.bunny.net',
            'https://fonts.googleapis.com',
            env('APP_ENV') === 'local' ? 'http://localhost:5173' : null,
        ])],

        [Directive::IMG, [Keyword::SELF, 'data:', 'blob:', 'https:']],

        [Directive::FONT, [Keyword::SELF, 'data:', 'https://fonts.bunny.net', 'https://fonts.gstatic.com']],

        [Directive::CONNECT, array_filter([
            Keyword::SELF,
            env('APP_ENV') === 'local' ? 'ws://localhost:5173' : null,
            env('APP_ENV') === 'local' ? 'wss://localhost:5173' : null,
            env('APP_ENV') === 'local' ? 'http://localhost:5173' : null,
        ])],

        in_array(env('APP_ENV'), ['local', 'testing'], true)
            ? [Directive::SCRIPT, [Keyword::SELF, Keyword::UNSAFE_EVAL, Keyword::UNSAFE_INLINE, 'http://localhost:5173']]
            : [Directive::SCRIPT, [Keyword::SELF, Keyword::UNSAFE_INLINE]],
    ]),

    'report_only_presets' => [
        //
    ],

    'report_only_directives' => [
        //
    ],

    'report_uri' => env('CSP_REPORT_URI', ''),

    'enabled' => env('CSP_ENABLED', true),

    'enabled_while_hot_reloading' => env('CSP_ENABLED_WHILE_HOT_RELOADING', true),

    'nonce_generator' => RandomString::class,

    /*
     * Disabled for Inertia/Vite compatibility (single script bundle, no per-script nonces).
     */
    'nonce_enabled' => env('CSP_NONCE_ENABLED', false),
];
