import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/css/app.css',
                'resources/js/app.js',
                'resources/js/stripe.js'
            ],
            refresh: true,
        }),
    ],
    build: {
        // Production optimizations
        minify: 'esbuild',
        cssMinify: true,
        rollupOptions: {
            output: {
                manualChunks: {
                    // Split vendor code for better caching
                    'vendor': ['alpinejs', 'chart.js'],
                    'laravel': ['axios', 'laravel-echo', 'pusher-js']
                }
            }
        },
        // Increase chunk size warning limit to 1MB
        chunkSizeWarningLimit: 1000,
    },
});
