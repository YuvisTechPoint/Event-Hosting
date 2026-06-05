import Echo from 'laravel-echo';
import Pusher from 'pusher-js';
import {getConfig} from './config.ts';
import {isSsr} from './helpers.ts';

declare global {
    interface Window {
        Pusher: typeof Pusher;
        Echo?: Echo<'pusher'>;
    }
}

let echoInstance: Echo<'pusher'> | null = null;
let connectionListenersAttached = false;

export function isEchoConfigured(): boolean {
    return Boolean(getConfig('VITE_PUSHER_APP_KEY'));
}

/** Alias used across realtime hooks/components. */
export const isRealtimeEnabled = isEchoConfigured;

export function getEcho(): Echo<'pusher'> | null {
    if (isSsr() || !isEchoConfigured()) {
        return null;
    }

    if (echoInstance) {
        return echoInstance;
    }

    window.Pusher = Pusher;

    const scheme = getConfig('VITE_PUSHER_SCHEME', 'https');
    const port = Number(getConfig('VITE_PUSHER_PORT', scheme === 'https' ? '443' : '80'));
    const apiUrl = getConfig('VITE_API_URL_CLIENT', '/api');

    echoInstance = new Echo({
        broadcaster: 'pusher',
        key: getConfig('VITE_PUSHER_APP_KEY')!,
        cluster: getConfig('VITE_PUSHER_APP_CLUSTER', 'mt1'),
        wsHost: getConfig('VITE_PUSHER_HOST') || window.location.hostname,
        wsPort: port,
        wssPort: port,
        forceTLS: scheme === 'https',
        enabledTransports: ['ws', 'wss'],
        disableStats: true,
        authEndpoint: `${apiUrl}/broadcasting/auth`,
        auth: {
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
            },
        },
        withCredentials: true,
    });

    window.Echo = echoInstance;
    attachConnectionListeners(echoInstance);

    return echoInstance;
}

function attachConnectionListeners(echo: Echo<'pusher'>): void {
    if (connectionListenersAttached) {
        return;
    }

    const connector = echo.connector as { pusher?: Pusher };
    const pusher = connector.pusher;

    if (!pusher?.connection) {
        return;
    }

    connectionListenersAttached = true;

    pusher.connection.bind('connected', () => {
        if (import.meta.env.DEV) {
            console.debug('[Echo] Connected');
        }
    });

    pusher.connection.bind('disconnected', () => {
        if (import.meta.env.DEV) {
            console.debug('[Echo] Disconnected — Pusher will auto-reconnect');
        }
    });

    pusher.connection.bind('unavailable', () => {
        if (import.meta.env.DEV) {
            console.debug('[Echo] Connection unavailable');
        }
    });
}

export function disconnectEcho(): void {
    if (echoInstance) {
        echoInstance.disconnect();
        echoInstance = null;
        connectionListenersAttached = false;
        delete window.Echo;
    }
}
