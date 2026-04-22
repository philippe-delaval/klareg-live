<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OverlaySetting extends Model
{
    protected $fillable = [
        'channel_name',
        'twitch_channel_id',
        'starting_title',
        'brb_message',
        'accent_color',
        'current_ticker',
        'sub_goal',
        'sub_current',
        'follower_goal',
        'follower_current',
        'now_playing_track',
        'now_playing_artist',
        'stream_title',
        'stream_category',
        'next_stream',
        'countdown_minutes',
        'countdown_seconds',
        'brb_duration_minutes',
        'socials',
        'ticker_messages',
    ];

    protected $casts = [
        'socials' => 'array',
        'ticker_messages' => 'array',
        'sub_goal' => 'integer',
        'sub_current' => 'integer',
        'follower_goal' => 'integer',
        'follower_current' => 'integer',
        'countdown_minutes' => 'integer',
        'countdown_seconds' => 'integer',
        'brb_duration_minutes' => 'integer',
    ];

    /**
     * Get or create the singleton settings row.
     */
    public static function current(): self
    {
        return self::firstOrCreate([], self::defaults());
    }

    /**
     * Default values matching the overlay config.js
     */
    public static function defaults(): array
    {
        return [
            'channel_name' => 'Klareg',
            'starting_title' => 'Lancement imminent',
            'brb_message' => 'De retour bientôt',
            'accent_color' => '#5B7FFF',
            'current_ticker' => 'Bienvenue sur le live ! Installez-vous confortablement.',
            'sub_goal' => 200,
            'sub_current' => 142,
            'follower_goal' => 5000,
            'follower_current' => 3840,
            'now_playing_track' => 'Synthwave Radio',
            'now_playing_artist' => 'Chill Beats',
            'stream_title' => 'Ranked Grind & Soirée Communautaire',
            'stream_category' => 'Just Chatting',
            'next_stream' => 'Demain à 18h00 CET',
            'countdown_minutes' => 5,
            'countdown_seconds' => 0,
            'brb_duration_minutes' => 5,
            'socials' => [
                ['platform' => 'twitch', 'url' => 'https://twitch.tv/Klareg', 'label' => 'Klareg'],
                ['platform' => 'twitter', 'url' => 'https://x.com/Klareg', 'label' => '@Klareg'],
                ['platform' => 'youtube', 'url' => 'https://youtube.com/@Klareg', 'label' => 'Klareg'],
                ['platform' => 'discord', 'url' => 'https://discord.gg/Klareg', 'label' => 'Discord'],
            ],
            'ticker_messages' => [
                'Bienvenue sur le live ! Installez-vous confortablement.',
                "Au programme aujourd'hui : Ranked Grind & Jeux Communautaires.",
                "N'oubliez pas le follow pour être notifié des prochains lives !",
                'Tapez !commands dans le chat pour voir les interactions disponibles.',
                'Abonnez-vous pour des emotes et des avantages !',
            ],
        ];
    }
}
