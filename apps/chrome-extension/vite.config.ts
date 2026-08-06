import { resolve } from 'node:path';
import { defineConfig } from 'vite';
import webExtension from 'vite-plugin-web-extension';

export default defineConfig({
    root: 'src',
    publicDir: resolve(__dirname, 'public'),
    base: '',
    build: {
        outDir: '../dist',
        emptyOutDir: true,
    },
    plugins: [
        webExtension({
            manifest: resolve(__dirname, 'src/manifest.json'),
        }),
    ],
});
