import { createApp } from 'vue';
import App from './App.vue';
import router from './router';
import { onUnauthenticated } from './services/api';
import { useAuth } from './composables/useAuth';

// A 401 in the middle of a session means it expired server-side.
onUnauthenticated(() => {
    useAuth().clearUser();

    const current = router.currentRoute.value;

    if (current.meta.requiresAuth) {
        router.push({ name: 'login', query: { redirect: current.fullPath } });
    }
});

createApp(App).use(router).mount('#app');
