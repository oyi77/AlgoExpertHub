import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import react from '@vitejs/plugin-react';
import tailwindcss from '@tailwindcss/vite';

export default defineConfig({
    plugins: [
        tailwindcss(),
        laravel({
            input: [
                'resources/js/admin-spa/main.tsx',
                'resources/js/app.jsx',
            ],
            refresh: true,
        }),
        react(),
    ],
    build: {
        sourcemap: true,
    },
    optimizeDeps: {
        include: ['lightweight-charts'],
    },
    resolve: {
        alias: {
            '@': '/resources/js/admin-spa',
        },
    },
});
