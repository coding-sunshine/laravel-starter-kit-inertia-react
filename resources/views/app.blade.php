<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" @class(['dark' => ($appearance ?? 'light') == 'dark'])>
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="description" content="RMMS is a Railway Rake Management System for tracking stock, dispatch, weighment, RR uploads, and penalty workflows.">
        <meta property="og:type" content="website">
        <meta property="og:title" content="RMMS | Railway Rake Management System">
        <meta property="og:description" content="Manage railway rakes, coal stock, weighments, RR documents, and penalties in one operational dashboard.">
        <meta property="og:url" content="{{ request()->fullUrl() }}">
        <meta property="og:image" content="{{ url('/logo.png') }}">
        
        {{-- Inline script to detect system dark mode preference and apply it immediately --}}
        <script>
            (function() {
                const appearance = '{{ $appearance ?? "light" }}';

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

        <meta name="csrf-token" content="{{ csrf_token() }}">
        <title inertia>{{ config('app.name') }}</title>

        <link rel="icon" type="image/png" sizes="1024x1024" href="/logo.png">
        <link rel="shortcut icon" type="image/png" href="/logo.png">
        <link rel="apple-touch-icon" href="/logo.png">
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
