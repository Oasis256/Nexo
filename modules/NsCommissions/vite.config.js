import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import vuePlugin from '@vitejs/plugin-vue';
import path from 'node:path';

export default defineConfig({
    plugins: [
        vuePlugin(),
        laravel({
            hotFile: 'Public/hot',
            input: [
                'Resources/ts/main.ts',
            ],
            refresh: [
                'Resources/**',
            ],
        }),
    ],
    resolve: {
        alias: {
            '@': path.resolve(__dirname, 'Resources/ts'),
            '~': path.resolve(__dirname, '../../resources/ts'),
        },
    },
    build: {
        outDir: 'Public/build',
        manifest: true,
        rollupOptions: {
            input: [
                './Resources/ts/main.ts',
            ],
            external: ['vue'],
            output: {
                globals: {
                    vue: 'Vue'
                }
            }
        },
    },
});
