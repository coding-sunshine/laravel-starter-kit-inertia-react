<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" @class(['dark' => ($appearance ?? 'system') == 'dark'])>
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        @isset($seo)
        @if(!empty($seo['meta_description']))
        <meta name="description" content="{{ $seo['meta_description'] }}">
        @endif
        <meta property="og:type" content="website">
        <meta property="og:title" content="{{ $seo['meta_title'] ?? config('app.name') }}">
        @if(!empty($seo['meta_description']))
        <meta property="og:description" content="{{ $seo['meta_description'] }}">
        @endif
        <meta property="og:url" content="{{ $seo['current_url'] ?? $seo['app_url'] ?? url()->current() }}">
        @if(!empty($seo['og_image']))
        <meta property="og:image" content="{{ $seo['og_image'] }}">
        @endif
        <meta name="twitter:card" content="summary_large_image">
        <meta name="twitter:title" content="{{ $seo['meta_title'] ?? config('app.name') }}">
        @if(!empty($seo['meta_description']))
        <meta name="twitter:description" content="{{ $seo['meta_description'] }}">
        @endif
        @endisset

        {{-- Inline script to detect system dark mode preference and apply it immediately --}}
        <script>
            (function() {
                const appearance = '{{ $appearance ?? "system" }}';

                if (appearance === 'system') {
                    const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;

                    if (prefersDark) {
                        document.documentElement.classList.add('dark');
                    }
                }
            })();
        </script>

        {{-- Inline style to set the HTML background color based on our theme in app.css --}}
        <style>
            html {
                background-color: oklch(1 0 0);
            }

            html.dark {
                background-color: oklch(0.145 0 0);
            }
        </style>

        <title inertia>{{ config('app.name', 'Laravel') }}</title>

        <link rel="icon" href="/favicon.ico" sizes="any">
        <link rel="icon" href="/favicon.svg" type="image/svg+xml">
        <link rel="apple-touch-icon" href="/apple-touch-icon.png">

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600" rel="stylesheet" />

        @viteReactRefresh
        @vite(['resources/js/app.tsx', "resources/js/pages/{$page['component']}.tsx"])
        @inertiaHead
    </head>
    <body class="font-sans antialiased">
        @inertia
        <x-impersonate::banner />
    </body>
</html>
