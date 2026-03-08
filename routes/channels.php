<?php

use Illuminate\Support\Facades\Broadcast;

// Private user channel (default, kept for compatibility)
Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

// Public competition channel — all contestants and judge subscribe here.
// Access is public because contestants don't have full auth sessions.
// The room_code is the shared secret for discovery.
Broadcast::channel('competition.{roomCode}', function () {
    return true; // public channel — all subscribers allowed
});
