<?php

use Illuminate\Support\Facades\Broadcast;

// Private user channel (default, kept for compatibility)
Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

// Public competition channel — all contestants and judge subscribe here.
// @ably/laravel-echo adds a 'public:' prefix for public channel subscriptions,
// so the channel name must match on both server and client.
Broadcast::channel('public:competition.{roomCode}', function () {
    return true; // public channel — all subscribers allowed
});
