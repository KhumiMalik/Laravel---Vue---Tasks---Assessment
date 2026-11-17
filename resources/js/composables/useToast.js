import { ref } from 'vue';

const toasts = ref([]);
let nextId = 0;

function dismiss(id) {
    toasts.value = toasts.value.filter((toast) => toast.id !== id);
}

function push(type, message, { action = null, timeout = 4000 } = {}) {
    const id = ++nextId;

    toasts.value.push({ id, type, message, action });

    if (timeout) {
        setTimeout(() => dismiss(id), timeout);
    }

    return id;
}

export function useToast() {
    return {
        toasts,
        dismiss,
        success: (message, options) => push('success', message, options),
        // errors stay up a little longer, people need time to read them
        error: (message, options) => push('error', message, { timeout: 6000, ...options }),
    };
}
