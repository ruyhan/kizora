import './bootstrap';
import '../css/app.css';

import { createApp, h } from 'vue';
import { createInertiaApp } from '@inertiajs/vue3';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { createPinia } from 'pinia';
import Toast from 'vue-toastification';
import 'vue-toastification/dist/index.css';
import 'swiper/css';
import 'swiper/css/navigation';
import 'swiper/css/pagination';
import { ZiggyVue } from '../../vendor/tightenco/ziggy';

const appName = import.meta.env.VITE_APP_NAME || 'Kizora';

createInertiaApp({
    title: (title) => title ? `${title} - ${appName}` : appName,
    resolve: (name) =>
        resolvePageComponent(
            `./Pages/${name}.vue`,
            import.meta.glob('./Pages/**/*.vue'),
        ),
    setup({ el, App, props, plugin }) {
        const pinia = createPinia();

        return createApp({ render: () => h(App, props) })
            .use(plugin)
            .use(pinia)
            .use(ZiggyVue)
            .use(Toast, {
                position: 'bottom-right',
                timeout: 3000,
                closeOnClick: true,
                pauseOnFocusLoss: false,
                pauseOnHover: true,
                draggable: false,
                showCloseButtonOnHover: false,
                hideProgressBar: false,
                closeButton: false,
                icon: true,
                rtl: false,
                maxToasts: 2,
                newestOnTop: true,
                transition: 'lavender-toast',
                // Prevent identical messages from stacking
                filterBeforeCreate: (toast, toasts) => {
                    if (toasts.some(t => t.content === toast.content && t.type === toast.type)) {
                        return false
                    }
                    return toast
                },
            })
            .mount(el);
    },
    progress: {
        color: '#ED1C24',
        showSpinner: true,
    },
});

// ── PWA service worker (root scope) ──
if ('serviceWorker' in navigator) {
    window.addEventListener('load', () => {
        navigator.serviceWorker.register('/sw.js', { scope: '/' }).catch(() => {});
    });
}
