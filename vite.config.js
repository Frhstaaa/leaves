import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import react from '@vitejs/plugin-react';

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.jsx'],
            refresh: true,
        }),
        react(),
    ],
    build: {
        cssCodeSplit: true,
        chunkSizeWarningLimit: 1000,
        minify: 'esbuild',
        rollupOptions: {
            output: {
                manualChunks: {
                    'vendor-core': [
                        'react',
                        'react-dom',
                        '@inertiajs/react',
                        'framer-motion',
                        'lucide-react'
                    ],
                    'vendor-swal': ['sweetalert2'],
                },
            },
        },
    },
});
