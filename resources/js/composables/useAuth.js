import { computed, readonly, ref } from 'vue';
import authService from '@/services/authService';

// module scope so every component shares the same signed-in user
const user = ref(null);
let initPromise = null;

export function useAuth() {
    const isAuthenticated = computed(() => user.value !== null);

    // Ask the API once per page load who we are. The session cookie is httpOnly,
    // so this is the only way the SPA can know. The router guard awaits it.
    function init() {
        initPromise ??= authService
            .fetchUser()
            .then((fetched) => {
                user.value = fetched;
            })
            .catch(() => {
                user.value = null;
            });

        return initPromise;
    }

    async function login(credentials) {
        user.value = await authService.login(credentials);
    }

    async function register(payload) {
        user.value = await authService.register(payload);
    }

    async function logout() {
        try {
            await authService.logout();
        } finally {
            clearUser();
        }
    }

    function clearUser() {
        user.value = null;
    }

    return {
        user: readonly(user),
        isAuthenticated,
        init,
        login,
        register,
        logout,
        clearUser,
    };
}
