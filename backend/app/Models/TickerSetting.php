<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TickerSetting extends Model
{
    protected $fillable = [
        'ticker_enabled',
        'ticker_speed',
        'messages',
        'scheduled_messages',
        'priority_messages',
        'weather_enabled',
        'music_enabled',
        'twitch_events_enabled',
        'twitch_events_follow',
        'twitch_events_sub',
        'crypto_enabled',
        'crypto_symbols',
        'crypto_refresh_minutes',
        'stats_enabled',
        'countdown_ticker_enabled',
        'countdown_ticker_target',
        'countdown_ticker_label',
        'chat_command_enabled',
        'chat_command_keyword',
        'emergency_enabled',
        'emergency_message',
        'emergency_color',
        'scene_gaming_enabled',
        'scene_brb_enabled',
        'scene_starting_enabled',
        'scene_chatting_enabled',
        'scene_screenshare_enabled',
    ];

    protected $casts = [
        'ticker_enabled' => 'boolean',
        'ticker_speed' => 'integer',
        'messages' => 'array',
        'scheduled_messages' => 'array',
        'priority_messages' => 'array',
        'weather_enabled' => 'boolean',
        'music_enabled' => 'boolean',
        'twitch_events_enabled' => 'boolean',
        'twitch_events_follow' => 'boolean',
        'twitch_events_sub' => 'boolean',
        'crypto_enabled' => 'boolean',
        'crypto_symbols' => 'array',
        'crypto_refresh_minutes' => 'integer',
        'stats_enabled' => 'boolean',
        'countdown_ticker_enabled' => 'boolean',
        'countdown_ticker_target' => 'datetime',
        'chat_command_enabled' => 'boolean',
        'emergency_enabled' => 'boolean',
        'scene_gaming_enabled' => 'boolean',
        'scene_brb_enabled' => 'boolean',
        'scene_starting_enabled' => 'boolean',
        'scene_chatting_enabled' => 'boolean',
        'scene_screenshare_enabled' => 'boolean',
    ];

    public static function current(): self
    {
        return self::firstOrCreate([], self::defaults());
    }

    public static function defaults(): array
    {
        return [
            'ticker_enabled' => true,
            'ticker_speed' => 60,
            'messages' => [
                ['text' => 'Bienvenue sur le live ! Installez-vous confortablement.', 'scene' => 'all', 'enabled' => true],
                ['text' => "Au programme aujourd'hui : Ranked Grind & Jeux Communautaires.", 'scene' => 'all', 'enabled' => true],
                ['text' => "N'oubliez pas le follow pour être notifié des prochains lives !", 'scene' => 'all', 'enabled' => true],
                ['text' => 'Tapez !commands dans le chat pour voir les interactions disponibles.', 'scene' => 'all', 'enabled' => true],
                ['text' => 'Abonnez-vous pour des emotes et des avantages !', 'scene' => 'all', 'enabled' => true],
            ],
            'scheduled_messages' => [],
            'priority_messages' => [],
            'weather_enabled' => false,
            'music_enabled' => false,
            'twitch_events_enabled' => false,
            'twitch_events_follow' => true,
            'twitch_events_sub' => true,
            'crypto_enabled' => false,
            'crypto_symbols' => ['BTC', 'ETH'],
            'crypto_refresh_minutes' => 5,
            'stats_enabled' => false,
            'countdown_ticker_enabled' => false,
            'countdown_ticker_target' => null,
            'countdown_ticker_label' => 'Prochain événement',
            'chat_command_enabled' => false,
            'chat_command_keyword' => '!ticker',
            'emergency_enabled' => false,
            'emergency_message' => null,
            'emergency_color' => '#FF4444',
            'scene_gaming_enabled' => true,
            'scene_brb_enabled' => false,
            'scene_starting_enabled' => true,
            'scene_chatting_enabled' => true,
            'scene_screenshare_enabled' => true,
        ];
    }
}
