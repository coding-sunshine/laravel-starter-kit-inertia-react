<?php

declare(strict_types=1);

use Spatie\Csp\Directive;
use Spatie\Csp\Keyword;
use Spatie\Csp\Nonce\RandomString;
use Spatie\Csp\Presets\Basic;

/*
 * Vite dev server origins (localhost + IPv6) for common ports so CSP allows scripts
 * when Vite uses 5173, 5174, etc. Only used when APP_ENV=local.
 */
$vitePorts = [5173, 5174, 5175, 5176, 5177];
$viteHttp = env('APP_ENV') === 'local' ? array_merge(
    array_map(fn (int $p) => "http://localhost:{$p}", $vitePorts),
    array_map(fn (int $p) => "http://127.0.0.1:{$p}", $vitePorts),
    array_map(fn (int $p) => "http://[::1]:{$p}", $vitePorts)
) : [];
$viteWs = env('APP_ENV') === 'local' ? array_merge(
    array_map(fn (int $p) => "ws://localhost:{$p}", $vitePorts),
    array_map(fn (int $p) => "ws://127.0.0.1:{$p}", $vitePorts),
    array_map(fn (int $p) => "wss://localhost:{$p}", $vitePorts),
    array_map(fn (int $p) => "wss://127.0.0.1:{$p}", $vitePorts),
    array_map(fn (int $p) => "ws://[::1]:{$p}", $vitePorts),
    array_map(fn (int $p) => "wss://[::1]:{$p}", $vitePorts)
) : [];

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
        [Directive::STYLE, array_filter(array_merge([
            Keyword::SELF,
            Keyword::UNSAFE_INLINE,
            'https://fonts.bunny.net',
            'https://fonts.googleapis.com',
            'https://unpkg.com',
        ], $viteHttp))],

        [Directive::IMG, [Keyword::SELF, 'data:', 'blob:', 'https:']],

        [Directive::FONT, [Keyword::SELF, 'data:', 'https://fonts.bunny.net', 'https://fonts.gstatic.com']],

        [Directive::CONNECT, array_filter(array_merge([
            Keyword::SELF,
            'https://maps.googleapis.com',
        ], $viteHttp, $viteWs))],

        in_array(env('APP_ENV'), ['local', 'testing'], true)
            ? [Directive::SCRIPT, array_merge([Keyword::SELF, Keyword::UNSAFE_EVAL, Keyword::UNSAFE_INLINE, 'https://unpkg.com', 'https://maps.googleapis.com'], $viteHttp)]
            : [Directive::SCRIPT, [Keyword::SELF, Keyword::UNSAFE_INLINE, 'https://unpkg.com', 'https://maps.googleapis.com']],

        // Chrome uses script-src-elem for <script> tags; set explicitly so Vite dev server is allowed.
        in_array(env('APP_ENV'), ['local', 'testing'], true)
            ? [Directive::SCRIPT_ELEM, array_merge([Keyword::SELF, Keyword::UNSAFE_EVAL, Keyword::UNSAFE_INLINE, 'https://unpkg.com', 'https://maps.googleapis.com'], $viteHttp)]
            : [Directive::SCRIPT_ELEM, [Keyword::SELF, Keyword::UNSAFE_INLINE, 'https://unpkg.com', 'https://maps.googleapis.com']],
    ]),

    'report_only_presets' => [],

    'report_only_directives' => [],

    // Managed via Filament: Settings > Security
    'report_uri' => '',

    'enabled' => true,

    'enabled_while_hot_reloading' => true,

    'nonce_generator' => RandomString::class,

    /*
     * Disabled for Inertia/Vite compatibility (single script bundle, no per-script nonces).
     */
    'nonce_enabled' => false,
];
