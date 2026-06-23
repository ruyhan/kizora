import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import vue from '@vitejs/plugin-vue';
import tailwindcss from '@tailwindcss/vite';
import { VitePWA } from 'vite-plugin-pwa';
import { resolve } from 'path';

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js'],
            refresh: true,
        }),
        vue({
            template: {
                transformAssetUrls: {
                    base: null,
                    includeAbsolute: false,
                },
            },
        }),
        tailwindcss(),
        VitePWA({
            registerType: 'autoUpdate',
            injectRegister: false, // registered manually via the /sw.js route (root scope)
            manifest: {
                name: 'Kizora',
                short_name: 'Kizora',
                description: 'Premium backpacks, bottles & flasks — built to move with you.',
                theme_color: '#17181B',
                background_color: '#FAFAFA',
                display: 'standalone',
                start_url: '/',
                scope: '/',
                icons: [
                    { src: '/icons/icon-192.png', sizes: '192x192', type: 'image/png' },
                    { src: '/icons/icon-512.png', sizes: '512x512', type: 'image/png' },
                    { src: '/icons/maskable-512.png', sizes: '512x512', type: 'image/png', purpose: 'maskable' },
                ],
            },
            workbox: {
                inlineWorkboxRuntime: true, // self-contained sw.js so it can be served from site root
                globPatterns: ['**/*.{js,css,woff2}'],
                navigateFallback: null,
                runtimeCaching: [
                    {
                        urlPattern: ({ request }) => request.mode === 'navigate',
                        handler: 'NetworkFirst',
                        options: { cacheName: 'kz-pages', networkTimeoutSeconds: 3, expiration: { maxEntries: 50 } },
                    },
                    {
                        urlPattern: ({ url }) => ['/build/', '/images/', '/icons/'].some((p) => url.pathname.startsWith(p)),
                        handler: 'StaleWhileRevalidate',
                        options: { cacheName: 'kz-assets', expiration: { maxEntries: 200 } },
                    },
                    {
                        urlPattern: ({ url }) => url.origin.includes('fonts.googleapis') || url.origin.includes('fonts.gstatic'),
                        handler: 'CacheFirst',
                        options: { cacheName: 'kz-fonts', expiration: { maxEntries: 20, maxAgeSeconds: 60 * 60 * 24 * 365 } },
                    },
                ],
            },
        }),
    ],
    resolve: {
        alias: {
            '@': resolve(__dirname, 'resources/js'),
        },
    },
    server: {
        watch: {
            ignored: ['**/storage/framework/views/**'],
        },
    },
});
