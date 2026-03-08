import './bootstrap';
import Echo from '@ably/laravel-echo';
import * as Ably from 'ably';

window.Echo = new Echo({
    broadcaster: 'ably',
    key: import.meta.env.VITE_ABLY_KEY,
    Ably,
});
