import { defineConfig, loadEnv } from 'vite';

import { fileURLToPath } from 'node:url';
import laravel from 'laravel-vite-plugin';
import path from 'node:path';
import vuePlugin from '@vitejs/plugin-vue';
import tailwindcss from '@tailwindcss/vite'

const Vue = fileURLToPath(
    new URL(
        'vue',
        import.meta.url
    )
);

export default ({ mode }) => {
    process.env = {...process.env, ...loadEnv(mode, process.cwd())};

    return defineConfig({
        base: '/',
        server: {
            host: 'localhost',
            port: 5432,
            strictPort: true,
            hmr: {
                host: 'localhost',
                port: 5432,
                protocol: 'ws',
            },
        },
        plugins: [
            vuePlugin(),
            tailwindcss(),
            laravel({
                hotFile: 'Public/hot',
                input: [
                    'Resources/ts/main.ts',
                    'Resources/ts/header.ts',
                    'Resources/css/multistore.css',
                ],
                refresh: [ 
                    'Resources/**', 
                ]
            })
        ],
        resolve: {
            alias: {
                '@': path.resolve(__dirname, 'Resources/ts'),
            }
        },
        build: {
            outDir: 'Public/build',
            manifest: true,
            rollupOptions: {
                input: [
                    './Resources/ts/main.ts',
                    './Resources/ts/header.ts',
                    './Resources/css/multistore.css',
                ],
            }
        }        
    });
}