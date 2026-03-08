import './bootstrap';
import Echo from '@ably/laravel-echo';
import * as Ably from 'ably';

window.Ably = Ably;

window.Echo = new Echo({
    broadcaster: 'ably',
    key: import.meta.env.VITE_ABLY_KEY,
    authEndpoint: '/ably-auth',
    authMethod: 'POST',
    authHeaders: {
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? '',
    },
});
