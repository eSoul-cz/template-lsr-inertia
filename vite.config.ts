import nette from '@nette/vite-plugin';
import tailwindcss from '@tailwindcss/vite';
import vue from '@vitejs/plugin-vue';
import path from 'path';
import { defineConfig } from 'vite';
import vueDevTools from 'vite-plugin-vue-devtools';

export default defineConfig({
    root: './',
    server: {
        strictPort: true,
        fs: {
            deny: ['.env', '.env.*', '*.{crt,pem}', '**/.git/**', `${import.meta.dirname}/{private,config,temp,logs,vendor,docker}/**`],
        },
    },
    build: {
        outDir: 'build',
        assetsDir: 'assets',
    },
    plugins: [
        nette({
            entry: ['assets/js/app.ts', 'assets/css/tailwind.css'],
        }),
        tailwindcss(),
        vueDevTools(),
        vue({
            template: {
                transformAssetUrls: {
                    base: null,
                    includeAbsolute: false,
                },
            },
        }),
    ],
    resolve: {
        alias: {
            '@': path.resolve(import.meta.dirname, './assets/js'),
        },
    },
});
