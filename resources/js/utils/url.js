export function getBaseUrl() {
    if (typeof document === 'undefined') return '';
    return document.querySelector('meta[name="app-base"]')?.content || '';
}

export function appUrl(path) {
    const base = getBaseUrl().replace(/\/+$/, '');
    const cleanPath = path.startsWith('/') ? path : `/${path}`;
    return `${base}${cleanPath}`;
}

