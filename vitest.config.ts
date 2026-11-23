import vue from '@vitejs/plugin-vue';
import { globSync, readFileSync } from 'node:fs';
import { fileURLToPath, URL } from 'node:url';
import { defineConfig } from 'vitest/config';

const testFiles = ['assets/js/**/*.test.{js,ts}'];

/**
 * Module mocks are registered on the worker's shared module graph, so with `isolate: false`
 * a file's `vi.mock()` either misses modules cached by earlier files or leaks into later ones.
 * Those files keep a fresh module graph; every other file shares one for speed.
 */
const moduleMockingTestFiles = globSync(testFiles).filter((file) => /\bvi\s*\.\s*(?:mock|doMock|unmock|doUnmock)\s*\(/.test(readFileSync(file, 'utf8')));

export default defineConfig({
    cacheDir: './temp/tools/vitest',
    plugins: [
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
            '@': fileURLToPath(new URL('./assets/js', import.meta.url)),
        },
    },
    test: {
        env: {
            TZ: 'Europe/Prague',
        },
        environment: 'node',
        projects: [
            {
                extends: true,
                test: {
                    name: 'shared',
                    include: testFiles,
                    exclude: moduleMockingTestFiles,
                    isolate: false,
                },
            },
            {
                extends: true,
                test: {
                    name: 'isolated',
                    include: moduleMockingTestFiles,
                    isolate: true,
                },
            },
        ],
    },
});
