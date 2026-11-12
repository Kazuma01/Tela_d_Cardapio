import Echo from 'laravel-echo';

import Pusher from 'pusher-js';
window.Pusher = Pusher;

// Em produção o Caddy serve WSS na 443 (mesmo host da página). Em dev, ws://.
const useTls = (import.meta.env.VITE_REVERB_SCHEME ?? 'https') === 'https';

window.Echo = new Echo({
    broadcaster: 'reverb',
    key: import.meta.env.VITE_REVERB_APP_KEY,
    wsHost: window.location.hostname,
    wsPort: import.meta.env.VITE_REVERB_PORT ?? 80,
    wssPort: import.meta.env.VITE_REVERB_PORT ?? 443,
    // O pusher-js não tem transporte "wss": o transporte é sempre "ws" e o
    // forceTLS decide se usa ws:// ou wss://.
    forceTLS: useTls,
    enabledTransports: ['ws'],
});
