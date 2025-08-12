import vue from '@vitejs/plugin-vue';
import laravel from 'laravel-vite-plugin';
import tailwindcss from '@tailwindcss/vite';
import { defineConfig } from 'vite';

export default defineConfig({
    server: {
        host: '0.0.0.0',    // bind inside Docker to all interfaces
        port: 5173,
        strictPort: true,
        cors: true,         // send Access-Control-Allow-Origin: *
        hmr: {
            host: '0.0.0.0',  // where the browser will connect for HMR
            protocol: 'ws',
            port: 5173,
        },
    },
    plugins: [
        laravel({
            input: ['resources/js/app.ts'],
            ssr: 'resources/js/ssr.ts',
            refresh: true,
        }),
        tailwindcss(),
        vue({
            template: {
                transformAssetUrls: {
                    base: null,
                    includeAbsolute: false,
                },
            },
        }),
    ],
});
