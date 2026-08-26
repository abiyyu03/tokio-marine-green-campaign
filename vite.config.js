import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import tailwindcss from '@tailwindcss/vite';

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js'],
            // Daftar eksplisit (menggantikan default plugin) supaya komponen
            // Livewire class-based di app/Livewire ikut memicu reload.
            refresh: [
                'resources/views/**',
                'routes/**',
                'app/View/Components/**',
                'app/Livewire/**',
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
