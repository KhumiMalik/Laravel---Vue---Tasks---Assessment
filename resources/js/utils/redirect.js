// Only follow ?redirect= to paths inside this app. "//evil.com" is protocol-relative,
// so a plain startsWith('/') check isn't enough.
export function safeRedirect(target, fallback = '/') {
    if (typeof target !== 'string' || !target.startsWith('/') || target.startsWith('//') || target.startsWith('/\\')) {
        return fallback;
    }

    return target;
}
