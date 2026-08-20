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
                    'vendor-react': ['react', 'react-dom'],
                    'vendor-inertia': ['@inertiajs/react'],
                    'vendor-motion': ['framer-motion'],
                    'vendor-icons': ['lucide-react'],
                    'vendor-swal': ['sweetalert2'],
                },
            },
        },
    },
});
