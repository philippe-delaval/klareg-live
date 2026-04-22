<?php

use Illuminate\Broadcasting\Channel;
use Illuminate\Support\Facades\Broadcast;

// Public channel — no authorization needed
Broadcast::channel('broadcast-overlay', function () {
    return true;
});

Broadcast::channel('twitch-events', function () {
    return true;
});

Broadcast::channel('twitch-chat', function () {
    return true;
});
