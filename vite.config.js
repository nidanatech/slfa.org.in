import { defineConfig } from 'vite'
import laravel from 'laravel-vite-plugin'
import react from '@vitejs/plugin-react'
import path from 'path'

export default defineConfig({
    resolve: {
        alias: {
            '@': path.resolve(__dirname, './resources/js'),
        },
    },

    server: {
        cors: true,
        hmr: {
            host: 'localhost',
        },
    },

    build: {
        sourcemap: false,
        reportCompressedSize: false,
        minify: 'esbuild',
        target: 'es2022',
        chunkSizeWarningLimit: 1500,

        rollupOptions: {
            output: {
                manualChunks: undefined,
            },
            maxParallelFileOps: 2,
        },
        manifest: 'manifest.json', // Force manifest to be at public/build/manifest.json
    },

    plugins: [
        laravel({
            input: 'resources/js/app.jsx',
            refresh: false,
        }),
        react({
            babel: {
                compact: true,
            },
        }),
    ],
})
