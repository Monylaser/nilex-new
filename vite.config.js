// vite.config.js (في جذر المشروع)
import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/css/app.css',
                'resources/js/app.js',
                'resources/js/filament/admin/custom.js', // سننشئ هذا الملف
            ],
            refresh: true,
        }),
    ],
});
