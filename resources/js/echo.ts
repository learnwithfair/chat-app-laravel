import Echo from 'laravel-echo'
import Pusher from 'pusher-js'
import axios from 'axios'

// Keep types minimal to avoid conflicts
type EchoInstance = Echo<any>

declare global {
    interface Window {
        Pusher: typeof Pusher
        Echo: EchoInstance
        axios: typeof axios
    }
    interface ImportMetaEnv {
        VITE_REVERB_APP_KEY: string
        VITE_REVERB_HOST?: string
        VITE_REVERB_PORT?: string
        VITE_REVERB_SCHEME?: 'http' | 'https'
    }
}

window.Pusher = Pusher
window.axios = axios
axios.defaults.withCredentials = true // if using Sanctum cookies

const wsHost = import.meta.env.VITE_REVERB_HOST ?? window.location.hostname
const port = Number(
    import.meta.env.VITE_REVERB_PORT ??
    (location.protocol === 'https:' ? 443 : 80)
)
const forceTLS =
    (import.meta.env.VITE_REVERB_SCHEME ?? location.protocol.replace(':', '')) === 'https'

// IMPORTANT: Use 'reverb' (not 'pusher')
window.Echo = new Echo({
    broadcaster: 'reverb',
    key: import.meta.env.VITE_REVERB_APP_KEY,
    wsHost,
    wsPort: port,
    wssPort: port,
    forceTLS,
    enabledTransports: ['ws', 'wss'],
    // No custom authorizer needed for Reverb
})
