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
        'now_playing_enabled',
        'stream_title',
        'stream_category',
        'next_stream',
        'countdown_minutes',
        'countdown_seconds',
        'brb_duration_minutes',
        'socials',
        'ticker_messages',
        'alert_follow_enabled',
        'alert_follow_duration',
        'alert_sub_enabled',
        'alert_sub_duration',
        'alert_resub_enabled',
        'alert_resub_duration',
        'alert_giftsub_enabled',
        'alert_giftsub_duration',
        'alert_bits_enabled',
        'alert_bits_duration',
        'alert_bits_min_amount',
        'alert_raid_enabled',
        'alert_raid_duration',
        'alert_raid_min_viewers',
        'alert_donation_enabled',
        'alert_donation_duration',
        'alert_hype_train_enabled',
        'chat_enabled',
        'chat_max_messages',
        'goal_sub_enabled',
        'goal_follower_enabled',
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
        'now_playing_enabled' => 'boolean',
        'alert_follow_enabled' => 'boolean',
        'alert_follow_duration' => 'integer',
        'alert_sub_enabled' => 'boolean',
        'alert_sub_duration' => 'integer',
        'alert_resub_enabled' => 'boolean',
        'alert_resub_duration' => 'integer',
        'alert_giftsub_enabled' => 'boolean',
        'alert_giftsub_duration' => 'integer',
        'alert_bits_enabled' => 'boolean',
        'alert_bits_duration' => 'integer',
        'alert_bits_min_amount' => 'integer',
        'alert_raid_enabled' => 'boolean',
        'alert_raid_duration' => 'integer',
        'alert_raid_min_viewers' => 'integer',
        'alert_donation_enabled' => 'boolean',
        'alert_donation_duration' => 'integer',
        'alert_hype_train_enabled' => 'boolean',
        'chat_enabled' => 'boolean',
        'chat_max_messages' => 'integer',
        'goal_sub_enabled' => 'boolean',
        'goal_follower_enabled' => 'boolean',
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
            'now_playing_enabled' => true,
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
            'alert_follow_enabled' => true,
            'alert_follow_duration' => 6,
            'alert_sub_enabled' => true,
            'alert_sub_duration' => 6,
            'alert_resub_enabled' => true,
            'alert_resub_duration' => 6,
            'alert_giftsub_enabled' => true,
            'alert_giftsub_duration' => 6,
            'alert_bits_enabled' => true,
            'alert_bits_duration' => 6,
            'alert_bits_min_amount' => 1,
            'alert_raid_enabled' => true,
            'alert_raid_duration' => 6,
            'alert_raid_min_viewers' => 1,
            'alert_donation_enabled' => true,
            'alert_donation_duration' => 6,
            'alert_hype_train_enabled' => true,
            'chat_enabled' => true,
            'chat_max_messages' => 50,
            'goal_sub_enabled' => true,
            'goal_follower_enabled' => true,
        ];
    }
}
