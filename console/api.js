function csrfToken() {
    return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
}

export async function api(path, options = {}) {
    const headers = {
        Accept: 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
        'X-CSRF-TOKEN': csrfToken(),
        ...(options.body instanceof FormData ? {} : { 'Content-Type': 'application/json' }),
        ...(options.headers || {}),
    };
    const response = await fetch(path, { credentials: 'same-origin', ...options, headers });
    if (response.status === 404) {
        throw new Error('Not found');
    }
    const data = await response.json().catch(() => ({}));
    if (!response.ok) {
        throw new Error(data.message || data.output || 'Request failed');
    }
    return data;
}
