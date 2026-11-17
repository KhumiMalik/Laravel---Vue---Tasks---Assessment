import { fileURLToPath, URL } from 'node:url';
import { defineConfig } from 'vitest/config';
import vue from '@vitejs/plugin-vue';

// Kept apart from vite.config.js on purpose: laravel-vite-plugin writes public/hot when a
// dev server starts, and a stale hot file makes Blade load assets from a dead dev server.
export default defineConfig({
    plugins: [vue()],
    resolve: {
        alias: {
            '@': fileURLToPath(new URL('./resources/js', import.meta.url)),
        },
    },
    test: {
        environment: 'jsdom',
        include: ['resources/js/**/*.test.js'],
    },
});
