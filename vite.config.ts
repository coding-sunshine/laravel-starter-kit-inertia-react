import { wayfinder } from '@laravel/vite-plugin-wayfinder';
import tailwindcss from '@tailwindcss/vite';
import react from '@vitejs/plugin-react';
import laravel from 'laravel-vite-plugin';
import { defineConfig } from 'vite';

export default defineConfig({
    server: {
        host: '127.0.0.1', // Use IPv4 so CSP/browsers don't block [::1] script sources
    },
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.tsx'],
            ssr: 'resources/js/ssr.tsx',
            refresh: true,
        }),
        react(),
        tailwindcss(),
        wayfinder({
            formVariants: true,
            // Use wrapper so non-zero exit from wayfinder:generate does not fail the build
            command: 'bash scripts/wayfinder-generate.sh',
        }),
    ],
    esbuild: {
        jsx: 'automatic',
    },
});
