/**
 * We'll load the axios HTTP library which allows us to easily issue requests
 * to our Laravel back-end. This library automatically handles sending the
 * CSRF token as a header based on the value of the "XSRF" token cookie.
 */

import axios from 'axios';
window.axios = axios;

window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';
window.axios.defaults.withCredentials = true;

if (typeof document !== 'undefined') {
    const token = document.head?.querySelector('meta[name="csrf-token"]');
    if (token && token.content) {
        window.axios.defaults.headers.common['X-CSRF-TOKEN'] = token.content;
    }
}

// Global Axios 419 Auto-Recovery Interceptor
window.axios.interceptors.response.use(
    (response) => {
        // If response provides a refreshed CSRF token in header, update meta tag & default headers
        const refreshedToken = response.headers?.['x-csrf-token'];
        if (refreshedToken && typeof document !== 'undefined') {
            const meta = document.head?.querySelector('meta[name="csrf-token"]');
            if (meta) meta.content = refreshedToken;
            window.axios.defaults.headers.common['X-CSRF-TOKEN'] = refreshedToken;
        }
        return response;
    },
    (error) => {
        if (error.response && error.response.status === 419) {
            console.warn('[CSO Security] Session expired (419). Auto-refreshing session...');
            if (typeof window !== 'undefined') {
                window.location.reload();
            }
        }
        return Promise.reject(error);
    }
);

/**
 * Echo exposes an expressive API for subscribing to channels and listening
 * for events that are broadcast by Laravel. Echo and event broadcasting
 * allows your team to easily build robust real-time web applications.
 */

// import Echo from 'laravel-echo';

// import Pusher from 'pusher-js';
// window.Pusher = Pusher;

// window.Echo = new Echo({
//     broadcaster: 'pusher',
//     key: import.meta.env.VITE_PUSHER_APP_KEY,
//     cluster: import.meta.env.VITE_PUSHER_APP_CLUSTER ?? 'mt1',
//     wsHost: import.meta.env.VITE_PUSHER_HOST ? import.meta.env.VITE_PUSHER_HOST : `ws-${import.meta.env.VITE_PUSHER_APP_CLUSTER}.pusher.com`,
//     wsPort: import.meta.env.VITE_PUSHER_PORT ?? 80,
//     wssPort: import.meta.env.VITE_PUSHER_PORT ?? 443,
//     forceTLS: (import.meta.env.VITE_PUSHER_SCHEME ?? 'https') === 'https',
//     enabledTransports: ['ws', 'wss'],
// });
