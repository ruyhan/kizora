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

// Titles prefixed with the zero-width-space marker (CMS meta titles, via
// utils/seoTitle.js) are shown exactly as typed — no " - appName" suffix.
const EXACT_TITLE_MARK = '\u200B';

createInertiaApp({
    title: (title) => {
        if (!title) return appName;
        if (title.startsWith(EXACT_TITLE_MARK)) return title.slice(1);
        return `${title} - ${appName}`;
    },
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

// ── Remove any stale service worker + caches (none is shipped; a leftover one would
//    keep serving old assets, so updates never appear). ──
if ('serviceWorker' in navigator) {
    navigator.serviceWorker.getRegistrations?.()
        .then((regs) => regs.forEach((r) => r.unregister()))
        .catch(() => {});
    if (window.caches?.keys) {
        caches.keys().then((keys) => keys.forEach((k) => caches.delete(k))).catch(() => {});
    }
}
