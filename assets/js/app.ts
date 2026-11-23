import { createInertiaApp } from '@inertiajs/vue3';
import { createApp, h, type DefineComponent } from 'vue';

const pages = import.meta.glob<DefineComponent>('./pages/**/*.vue', { import: 'default' });

createInertiaApp({
    id: 'app',
    resolve: (name) => {
        const load = pages[`./pages/${name}.vue`];
        if (!load) {
            throw new Error(`Unknown Inertia page: ${name}`);
        }
        return load();
    },
    setup({ el, App, props, plugin }) {
        createApp({ render: () => h(App, props) })
            .use(plugin)
            .mount(el);
    },
});
