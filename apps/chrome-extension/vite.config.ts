import { defineConfig } from 'vite';
import webExtension from 'vite-plugin-web-extension';
import { resolve } from 'node:path';

export default defineConfig({
    root: 'src',
    build: {
        outDir: '../dist',
        emptyOutDir: true,
    },
    plugins: [
        webExtension({
            manifest: resolve(__dirname, 'src/manifest.json'),
            additionalInputs: {
                html: ['popup.html', 'options.html'],
            },
        }),
    ],
});
