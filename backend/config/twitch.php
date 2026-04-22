<?php

return [

    'client_id' => env('TWITCH_CLIENT_ID'),
    'client_secret' => env('TWITCH_CLIENT_SECRET'),
    'channel_name' => env('TWITCH_CHANNEL_NAME'),
    'channel_id' => env('TWITCH_CHANNEL_ID'),

    'eventsub_url' => env('TWITCH_EVENTSUB_URL', 'wss://eventsub.wss.twitch.tv/ws'),
    'irc_url' => env('TWITCH_IRC_URL', 'wss://irc-ws.chat.twitch.tv:443'),

    'token_url' => 'https://id.twitch.tv/oauth2/token',
    'authorize_url' => 'https://id.twitch.tv/oauth2/authorize',
    'helix_url' => 'https://api.twitch.tv/helix',

    // Where Twitch will send the user back after authorisation. MUST match
    // one of the redirect URIs registered on the Twitch app at
    // https://dev.twitch.tv/console/apps.
    'redirect_uri' => env('TWITCH_REDIRECT_URI', env('APP_URL').'/twitch/oauth/callback'),

    // OAuth scopes required for EventSub WebSocket subscriptions.
    'oauth_scopes' => [
        'moderator:read:followers',     // channel.follow v2
        'channel:read:subscriptions',   // channel.subscribe, subscription.gift, subscription.message
        'bits:read',                    // channel.cheer
        'channel:read:redemptions',     // channel_points_custom_reward_redemption.add
        'channel:read:hype_train',      // channel.hype_train.*
    ],

    'event_types' => [
        ['type' => 'channel.follow', 'version' => '2'],
        ['type' => 'channel.subscribe', 'version' => '1'],
        ['type' => 'channel.subscription.gift', 'version' => '1'],
        ['type' => 'channel.subscription.message', 'version' => '1'],
        ['type' => 'channel.cheer', 'version' => '1'],
        ['type' => 'channel.raid', 'version' => '1'],
        ['type' => 'channel.channel_points_custom_reward_redemption.add', 'version' => '1'],
        ['type' => 'channel.hype_train.begin', 'version' => '2'],
        ['type' => 'channel.hype_train.progress', 'version' => '2'],
        ['type' => 'channel.hype_train.end', 'version' => '2'],
        ['type' => 'stream.online', 'version' => '1'],
        ['type' => 'stream.offline', 'version' => '1'],
    ],

];
