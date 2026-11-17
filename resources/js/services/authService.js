import api, { fetchCsrfCookie } from './api';

export default {
    async login(credentials) {
        await fetchCsrfCookie();
        const { data } = await api.post('/login', credentials);
        return data.data;
    },

    async register(payload) {
        await fetchCsrfCookie();
        const { data } = await api.post('/register', payload);
        return data.data;
    },

    async logout() {
        await api.post('/logout');
    },

    async fetchUser() {
        // a 401 here just means "not logged in yet", not an expired session
        const { data } = await api.get('/user', { skipAuthRedirect: true });
        return data.data;
    },
};
