import axios from 'axios';

const api = axios.create({
    baseURL: '/api',
    withCredentials: true,
    withXSRFToken: true,
    headers: {
        Accept: 'application/json',
        'Content-Type': 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
    },
});

export function fetchCsrfCookie() {
    return axios.get('/sanctum/csrf-cookie', { withCredentials: true });
}

let handleUnauthenticated = () => {};

export function onUnauthenticated(handler) {
    handleUnauthenticated = handler;
}

api.interceptors.response.use(
    (response) => response,
    async (error) => {
        const { response, config } = error;

        // 419 = CSRF token expired (tab left open past the session lifetime).
        // Grab a fresh cookie and replay once; axios re-reads the cookie on the retry.
        if (response?.status === 419 && config && !config._csrfRetried) {
            config._csrfRetried = true;
            await fetchCsrfCookie();
            return api(config);
        }

        if (response?.status === 401 && !config?.skipAuthRedirect) {
            handleUnauthenticated();
        }

        return Promise.reject(toApiError(error));
    },
);

function toApiError(error) {
    const status = error.response?.status ?? 0;
    const data = error.response?.data ?? {};

    let message = data.message || 'Request failed.';

    if (!error.response) {
        message = 'Could not reach the server. Check your connection and try again.';
    } else if (status >= 500) {
        // never surface server internals, even if debug mode leaks them into the body
        message = 'Something went wrong on our side. Please try again.';
    } else if (status === 429 && !data.errors) {
        message = 'Too many requests. Please wait a moment and try again.';
    }

    const apiError = new Error(message);
    apiError.status = status;
    apiError.errors = data.errors ?? {};

    return apiError;
}

export default api;
