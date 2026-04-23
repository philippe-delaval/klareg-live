<?php

namespace App\Http\Controllers;

use App\Models\OverlaySetting;
use App\Models\Schedule;
use App\Models\TwitchToken;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;

class OverlayApiController extends Controller
{
    public const SETTINGS_CACHE_KEY = 'overlay:settings:payload';

    public const SETTINGS_CACHE_TTL = 3600;

    /**
     * Public Reverb connection parameters for overlay clients.
     * NEVER includes REVERB_APP_SECRET — only the client-safe key, host, port, scheme.
     */
    public function reverbConfig(): JsonResponse
    {
        return response()->json([
            'key' => config('broadcasting.connections.reverb.key'),
            'host' => config('broadcasting.connections.reverb.options.host'),
            'port' => (int) config('broadcasting.connections.reverb.options.port'),
            'scheme' => config('broadcasting.connections.reverb.options.scheme', 'https'),
        ]);
    }

    /**
     * Get all overlay settings as JSON (for frontend initialization)
     */
    public function settings(): JsonResponse
    {
        $payload = Cache::remember(
            self::SETTINGS_CACHE_KEY,
            self::SETTINGS_CACHE_TTL,
            static function (): array {
                $settings = OverlaySetting::current();

                return [
                    'channel_name' => $settings->channel_name,
                    'starting_title' => $settings->starting_title,
                    'starting_soon_bg_style' => $settings->starting_soon_bg_style ?? 'aurora',
                    'brb_bg_style' => $settings->brb_bg_style ?? 'none',
                    'ending_bg_style' => $settings->ending_bg_style ?? 'none',
                    'color_presets' => $settings->color_presets ?? [],
                    'brb_message' => $settings->brb_message,
                    'accent_color' => $settings->accent_color,
                    'overlay_theme' => $settings->overlay_theme ?? 'default',
                    'current_ticker' => $settings->current_ticker,
                    'sub_goal' => $settings->sub_goal,
                    'sub_current' => $settings->sub_current,
                    'follower_goal' => $settings->follower_goal,
                    'follower_current' => $settings->follower_current,
                    'now_playing_track' => $settings->now_playing_track,
                    'now_playing_artist' => $settings->now_playing_artist,
                    'now_playing_enabled' => (bool) $settings->now_playing_enabled,
                    'stream_title' => $settings->stream_title,
                    'stream_category' => $settings->stream_category,
                    'next_stream' => $settings->next_stream,
                    'next_stream_enabled' => (bool) $settings->next_stream_enabled,
                    'countdown_minutes' => $settings->countdown_minutes,
                    'countdown_seconds' => $settings->countdown_seconds,
                    'brb_duration_minutes' => $settings->brb_duration_minutes,
                    'socials' => $settings->socials,
                    'ticker_messages' => $settings->ticker_messages,
                    'alert_follow_enabled' => (bool) $settings->alert_follow_enabled,
                    'alert_follow_duration' => $settings->alert_follow_duration,
                    'alert_sub_enabled' => (bool) $settings->alert_sub_enabled,
                    'alert_sub_duration' => $settings->alert_sub_duration,
                    'alert_resub_enabled' => (bool) $settings->alert_resub_enabled,
                    'alert_resub_duration' => $settings->alert_resub_duration,
                    'alert_giftsub_enabled' => (bool) $settings->alert_giftsub_enabled,
                    'alert_giftsub_duration' => $settings->alert_giftsub_duration,
                    'alert_bits_enabled' => (bool) $settings->alert_bits_enabled,
                    'alert_bits_duration' => $settings->alert_bits_duration,
                    'alert_bits_min_amount' => $settings->alert_bits_min_amount,
                    'alert_raid_enabled' => (bool) $settings->alert_raid_enabled,
                    'alert_raid_duration' => $settings->alert_raid_duration,
                    'alert_raid_min_viewers' => $settings->alert_raid_min_viewers,
                    'alert_donation_enabled' => (bool) $settings->alert_donation_enabled,
                    'alert_donation_duration' => $settings->alert_donation_duration,
                    'alert_hype_train_enabled' => (bool) $settings->alert_hype_train_enabled,
                    'chat_enabled' => (bool) $settings->chat_enabled,
                    'chat_max_messages' => $settings->chat_max_messages,
                    'goal_sub_enabled' => (bool) $settings->goal_sub_enabled,
                    'goal_follower_enabled' => (bool) $settings->goal_follower_enabled,
                ];
            }
        );

        return response()->json($payload);
    }

    /**
     * Get schedule entries
     */
    public function schedule(): JsonResponse
    {
        return response()->json(
            Schedule::active()->ordered()->get(['time', 'label', 'is_active'])
        );
    }

    /**
     * Get Twitch connection status
     */
    public function twitchStatus(): JsonResponse
    {
        $token = TwitchToken::current();

        return response()->json([
            'eventsub' => [
                'status' => Cache::get('twitch:eventsub:status', 'disconnected'),
                'connected_at' => Cache::get('twitch:eventsub:connected_at'),
            ],
            'irc' => [
                'status' => Cache::get('twitch:irc:status', 'disconnected'),
                'connected_at' => Cache::get('twitch:irc:connected_at'),
            ],
            'token' => [
                'is_valid' => $token ? ! $token->isExpired() : false,
                'expires_at' => $token?->expires_at?->toISOString(),
            ],
        ]);
    }
}
