<?php

declare(strict_types=1);

use Spatie\Csp\Directive;
use Spatie\Csp\Keyword;
use Spatie\Csp\Nonce\RandomString;
use Spatie\Csp\Presets\Basic;

$isLocal = in_array(env('APP_ENV'), ['local', 'testing'], true);

$viteOrigins = $isLocal ? [
    'http://localhost:5173',
    'http://127.0.0.1:5173',
    'http://[::1]:5173',
] : [];

$viteWs = $isLocal ? [
    'ws://localhost:5173',
    'ws://127.0.0.1:5173',
    'ws://[::1]:5173',
    'wss://localhost:5173',
    'wss://127.0.0.1:5173',
    'wss://[::1]:5173',
] : [];

return [

    'presets' => [
        Basic::class,
    ],

    'directives' => [
        [Directive::STYLE, array_merge(
            [Keyword::SELF, Keyword::UNSAFE_INLINE, 'https://fonts.bunny.net', 'https://fonts.googleapis.com', 'https://unpkg.com'],
            $viteOrigins,
        )],

        [Directive::IMG, [Keyword::SELF, 'data:', 'blob:', 'https:']],

        [Directive::FONT, [Keyword::SELF, 'data:', 'https://fonts.bunny.net', 'https://fonts.gstatic.com']],

        [Directive::CONNECT, array_merge(
            [Keyword::SELF],
            $viteOrigins,
            $viteWs,
        )],

        $isLocal
            ? [Directive::SCRIPT, array_merge(
                [Keyword::SELF, Keyword::UNSAFE_EVAL, Keyword::UNSAFE_INLINE, 'https://unpkg.com'],
                $viteOrigins,
            )]
            : [Directive::SCRIPT, [Keyword::SELF, Keyword::UNSAFE_INLINE, 'https://unpkg.com']],
    ],

    'report_only_presets' => [],

    'report_only_directives' => [],

    'report_uri' => '',

    'enabled' => false,

    'enabled_while_hot_reloading' => true,

    'nonce_generator' => RandomString::class,

    'nonce_enabled' => false,
];
