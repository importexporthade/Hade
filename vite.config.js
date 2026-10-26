import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import { bunny } from 'laravel-vite-plugin/fonts';
import tailwindcss from '@tailwindcss/vite';

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js', 'resources/css/filament/manager/theme.css'],
            refresh: true,
            fonts: [
                bunny('Albert Sans', {
                    weights: [400, 500, 600, 700],
                }),
                bunny('Playfair Display', {
                    weights: [400, 500, 600, 700],
                }),
                bunny('Montserrat', {
                    weights: [400, 500, 600, 700],
                }),
                bunny('Cormorant Garamond', {
                    weights: [400, 500, 600, 700],
                }),
            ],
        }),
        tailwindcss(),
    ],
    server: {
        watch: {
            ignored: ['**/storage/framework/views/**'],
        },
    },
});
