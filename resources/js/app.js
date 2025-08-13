import { createApp, h } from 'vue';
import { createInertiaApp } from '@inertiajs/inertia-vue3';

import '../css/app.css';
import 'primeicons/primeicons.css';
import PrimeVue from 'primevue/config';
import Material from '@primeuix/themes/material';
import ToastService from 'primevue/toastservice';
import ConfirmationService from "primevue/confirmationservice";

createInertiaApp({
    resolve: name => import(`./pages/home/${name}.vue`),
    setup({ el, App, props, plugin }) {
        const vueApp = createApp({ render: () => h(App, props) });

        vueApp.use(plugin);
        vueApp.use(PrimeVue, {
            theme: {
                preset: Material,
                options: {
                    prefix: 'p',
                    darkModeSelector: 'system',
                    cssLayer: false
                }
            }
        });
        vueApp.use(ToastService);
        vueApp.use(ConfirmationService);
        vueApp.mount(el);
    },
});
