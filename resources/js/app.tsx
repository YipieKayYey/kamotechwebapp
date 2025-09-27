import '../css/app.css';
import '../css/auth.css';
import '../css/services.css';
import '../css/reviews.css';

import { createInertiaApp } from '@inertiajs/react';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { createRoot } from 'react-dom/client';
import { initializeTheme } from './hooks/use-appearance';
import axios from 'axios';

// Configure axios for session-based authentication
axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';
axios.defaults.headers.common['Accept'] = 'application/json';
axios.defaults.withCredentials = true;

// Get CSRF token from meta tag
const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
if (token) {
    axios.defaults.headers.common['X-CSRF-TOKEN'] = token;
}

// Add response interceptor to handle 410 errors globally
axios.interceptors.response.use(
    response => response,
    error => {
        if (error.response?.status === 410) {
            console.error('API endpoint no longer available (410). The API format may have changed.');
            console.error('Request URL:', error.config?.url);
            console.error('Request data:', error.config?.data);
            
            // Provide user-friendly error message
            if (error.config?.url?.includes('/booking/')) {
                console.error('Booking API error: Please refresh the page and try again.');
            }
        }
        if (error.response?.status === 419) {
            // Session or CSRF token expired. Reload to refresh the token/session.
            window.location.reload();
            return; // stop further handling
        }
        
        // Always reject the promise so individual error handlers can still run
        return Promise.reject(error);
    }
);

const appName = import.meta.env.VITE_APP_NAME || 'Laravel';

createInertiaApp({
    title: (title) => title ? `${title} - ${appName}` : appName,
    resolve: (name) => resolvePageComponent(`./pages/${name}.tsx`, import.meta.glob('./pages/**/*.tsx')),
    setup({ el, App, props }) {
        const root = createRoot(el);

        root.render(<App {...props} />);
    },
    progress: {
        color: '#4B5563',
    },
});

// This will set light / dark mode on load...
initializeTheme();

// Refresh the page automatically when the backend returns a 419 (Page Expired)
// Works for Inertia navigations
window.addEventListener('inertia:error', (event: any) => {
    const status = event?.detail?.response?.status;
    if (status === 419) {
        window.location.reload();
    }
});
