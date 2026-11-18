import api from './api';

export default {
    // Open-Meteo is only ever called from the backend; this hits our own /api/weather
    async current(city) {
        const { data } = await api.get('/weather', { params: { city } });
        return data.data;
    },
};
