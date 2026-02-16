import Echo from 'laravel-echo';
import Pusher from 'pusher-js';
import axios from 'axios';

window.Pusher = Pusher;
window.axios = axios;

// Enable credentials for session-based auth
axios.defaults.withCredentials = true;  // if using Sanctum cookies

const wsHost = import.meta.env.VITE_REVERB_HOST ?? window.location.hostname;
const port = Number(import.meta.env.VITE_REVERB_PORT ?? (location.protocol === 'https:' ? 443 : 80));
const forceTLS = (import.meta.env.VITE_REVERB_SCHEME ?? location.protocol.replace(':', '')) === 'https';

// Simple Echo configuration - just like your working TypeScript project
window.Echo = new Echo({
    broadcaster: 'reverb',
    key: import.meta.env.VITE_REVERB_APP_KEY,
    wsHost,
    wsPort: port,
    wssPort: port,
    forceTLS,
    enabledTransports: ['ws', 'wss'],
});

console.log('✅ Echo initialized');