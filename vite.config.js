import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import { bunny } from 'laravel-vite-plugin/fonts';
import tailwindcss from '@tailwindcss/vite';

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js'],
            refresh: true,
            fonts: [
                bunny('Instrument Sans', {
                    weights: [400, 500, 600],
                }),
            ],
        }),
        tailwindcss(),
    ],
    server: {
        // Docker 内で起動し、ホストの 5174 番から HMR を受ける
        host: '0.0.0.0',
        port: 5173,
        strictPort: true,
        origin: 'http://localhost:5174',
        hmr: {
            host: 'localhost',
            clientPort: 5174,
        },
        watch: {
            ignored: ['**/storage/framework/views/**'],
        },
    },
});
