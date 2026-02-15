import { defineConfig } from 'vite';
import { fileURLToPath } from 'node:url';
import laravel from 'laravel-vite-plugin';
import mkcert from 'vite-plugin-mkcert';
import path from 'node:path';

export default defineConfig({
    base: '/',
    plugins: [
        mkcert(),
        laravel({
            hotFile: 'Public/hot',
            input: [
                'Resources/ts/main.ts',
                'Resources/ts/dashboard.ts',
                'Resources/ts/dashboard-entry.ts',
            ],
            refresh: [ 
                'Resources/**', 
            ]
        }),
    ],
    resolve: {
        alias: {
            '@': path.resolve(__dirname, 'Resources/ts'),
        }
    },
    server: {
        port: 3355,
        host: '127.0.0.1',
        cors: true,
        hmr: {
            protocol: 'wss',
            host: 'localhost',
        },
        https: true,
    },
    build: {
        outDir: 'Public/build',
        manifest: true,
        rollupOptions: {
            input: [
                './Resources/ts/main.ts',
                './Resources/ts/dashboard.ts',
                './Resources/ts/dashboard-entry.ts',
            ],
        }
    }
});
