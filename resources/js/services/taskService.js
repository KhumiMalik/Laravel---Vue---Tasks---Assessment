import api from './api';

export default {
    async list(params = {}) {
        const { data } = await api.get('/tasks', { params });
        return data;
    },

    async get(id) {
        const { data } = await api.get(`/tasks/${id}`);
        return data.data;
    },

    async create(payload) {
        const { data } = await api.post('/tasks', payload);
        return data.data;
    },

    async update(id, payload) {
        const { data } = await api.patch(`/tasks/${id}`, payload);
        return data.data;
    },

    async toggleCompletion(id) {
        const { data } = await api.patch(`/tasks/${id}/toggle-completion`);
        return data.data;
    },

    async remove(id) {
        await api.delete(`/tasks/${id}`);
    },

    async statistics() {
        const { data } = await api.get('/tasks/statistics');
        return data.data;
    },

    async trash(params = {}) {
        const { data } = await api.get('/tasks/trash', { params });
        return data;
    },

    async restore(id) {
        const { data } = await api.post(`/tasks/${id}/restore`);
        return data.data;
    },

    async forceDelete(id) {
        await api.delete(`/tasks/${id}/force-delete`);
    },

    async activities() {
        const { data } = await api.get('/activities');
        return data.data;
    },
};
