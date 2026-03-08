import './bootstrap';
import Echo from '@ably/laravel-echo';
import * as Ably from 'ably';

window.Ably = Ably;

window.Echo = new Echo({
    broadcaster: 'ably',
    // No 'key' here — we use token auth via authEndpoint.
    // Passing key (even undefined) causes Ably SDK to crash on key.split(':')
    authEndpoint: '/ably-auth',
    authMethod: 'GET',
});
