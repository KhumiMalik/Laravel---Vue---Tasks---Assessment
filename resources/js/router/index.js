import { createRouter, createWebHistory } from 'vue-router';
import { useAuth } from '@/composables/useAuth';

const appName = import.meta.env.VITE_APP_NAME || 'TaskFlow';

const routes = [
    {
        path: '/login',
        name: 'login',
        component: () => import('@/pages/Login.vue'),
        meta: { guest: true, title: 'Sign in' },
    },
    {
        path: '/register',
        name: 'register',
        component: () => import('@/pages/Register.vue'),
        meta: { guest: true, title: 'Create account' },
    },
    {
        path: '/',
        name: 'dashboard',
        component: () => import('@/pages/Dashboard.vue'),
        meta: { requiresAuth: true, title: 'My tasks' },
    },
    {
        path: '/tasks/:id(\\d+)',
        name: 'tasks.show',
        component: () => import('@/pages/TaskDetail.vue'),
        props: (route) => ({ id: Number(route.params.id) }),
        meta: { requiresAuth: true, title: 'Task details' },
    },
    {
        path: '/trash',
        name: 'trash',
        component: () => import('@/pages/Trash.vue'),
        meta: { requiresAuth: true, title: 'Trash' },
    },
    {
        path: '/weather',
        name: 'weather',
        component: () => import('@/pages/Weather.vue'),
        meta: { requiresAuth: true, title: 'Weather' },
    },
    {
        path: '/:pathMatch(.*)*',
        redirect: { name: 'dashboard' },
    },
];

const router = createRouter({
    history: createWebHistory(),
    routes,
    scrollBehavior: () => ({ top: 0 }),
});

router.beforeEach(async (to) => {
    const { init, isAuthenticated } = useAuth();

    await init();

    if (to.meta.requiresAuth && !isAuthenticated.value) {
        return {
            name: 'login',
            query: to.fullPath === '/' ? {} : { redirect: to.fullPath },
        };
    }

    if (to.meta.guest && isAuthenticated.value) {
        return { name: 'dashboard' };
    }
});

router.afterEach((to) => {
    document.title = to.meta.title ? `${to.meta.title} · ${appName}` : appName;
});

export default router;
