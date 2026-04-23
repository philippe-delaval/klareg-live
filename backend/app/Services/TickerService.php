<?php

namespace App\Services;

use App\Models\OverlaySetting;
use App\Models\ServiceConnection;
use App\Models\TickerSetting;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;

class TickerService
{
    public function __construct(
        private readonly WeatherService $weather,
        private readonly CryptoService $crypto,
        private readonly SpotifyService $spotify,
    ) {}

    public function compile(?string $scene = null): array
    {
        $setting = TickerSetting::current();

        if (! $setting->ticker_enabled) {
            return [];
        }

        // Emergency mode: only one message shown
        if ($setting->emergency_enabled && $setting->emergency_message) {
            return [[
                'type' => 'emergency',
                'text' => $setting->emergency_message,
                'icon' => 'ph:warning',
                'color' => $setting->emergency_color,
            ]];
        }

        $items = [];

        // Priority messages (not yet expired)
        foreach ($setting->priority_messages ?? [] as $pm) {
            $expiresAt = isset($pm['expires_at']) ? Carbon::parse($pm['expires_at']) : null;
            if ($expiresAt && now()->gt($expiresAt)) {
                continue;
            }
            $items[] = ['type' => 'priority', 'text' => $pm['message'] ?? '', 'icon' => 'ph:bell-ringing'];
        }

        // Scheduled messages (currently active window)
        foreach ($setting->scheduled_messages ?? [] as $sm) {
            $start = isset($sm['starts_at']) ? Carbon::parse($sm['starts_at']) : null;
            $end = isset($sm['ends_at']) ? Carbon::parse($sm['ends_at']) : null;
            $now = now();
            if (($start && $now->lt($start)) || ($end && $now->gt($end))) {
                continue;
            }
            $items[] = ['type' => 'scheduled', 'text' => $sm['message'] ?? '', 'icon' => 'ph:clock'];
        }

        // Custom messages (with scene filter)
        foreach ($setting->messages ?? [] as $msg) {
            if (! ($msg['enabled'] ?? true)) {
                continue;
            }
            $msgScene = $msg['scene'] ?? 'all';
            if ($msgScene !== 'all' && $scene && $msgScene !== $scene) {
                continue;
            }
            if ($msg['text'] ?? '') {
                $items[] = ['type' => 'message', 'text' => $msg['text'], 'icon' => null];
            }
        }

        // Fallback to overlay_settings messages if none defined
        if (empty($items)) {
            $overlay = OverlaySetting::current();
            foreach ($overlay->ticker_messages ?? [] as $text) {
                $items[] = ['type' => 'message', 'text' => $text, 'icon' => null];
            }
        }

        $svc = ServiceConnection::current();

        // Weather
        if ($setting->weather_enabled && $svc->weather_enabled && $svc->weather_api_key) {
            $weatherText = Cache::remember(
                "ticker:weather:{$svc->weather_city}",
                900,
                fn () => $this->weather->getFormatted(
                    $svc->weather_city,
                    $svc->weather_api_key,
                    $svc->weather_units ?? 'metric'
                )
            );
            if ($weatherText) {
                $items[] = ['type' => 'weather', 'text' => $weatherText, 'icon' => 'ph:cloud-sun'];
            }
        }

        // Music (Spotify now playing)
        if ($setting->music_enabled && $svc->spotify_enabled && $svc->isSpotifyConnected()) {
            $track = Cache::remember('ticker:spotify', 15, fn () => $this->spotify->getNowPlaying(
                $svc->spotify_client_id,
                $svc->spotify_client_secret,
                $svc->spotify_refresh_token,
            ));
            if ($track) {
                $items[] = ['type' => 'music', 'text' => "♪ {$track}", 'icon' => 'ph:spotify-logo'];
            }
        }

        // Crypto prices
        if ($setting->crypto_enabled && ! empty($setting->crypto_symbols)) {
            $prices = Cache::remember(
                'ticker:crypto',
                ($setting->crypto_refresh_minutes ?? 5) * 60,
                fn () => $this->crypto->getPrices($setting->crypto_symbols)
            );
            foreach ($prices as $price) {
                $items[] = ['type' => 'crypto', 'text' => $price, 'icon' => 'ph:chart-line-up'];
            }
        }

        // Stream stats (viewer count cached by Twitch events handler)
        if ($setting->stats_enabled) {
            $viewers = Cache::get('overlay:viewer_count', 0);
            if ($viewers > 0) {
                $items[] = ['type' => 'stats', 'text' => "{$viewers} viewers en ce moment", 'icon' => 'ph:eye'];
            }
        }

        // Countdown
        if ($setting->countdown_ticker_enabled && $setting->countdown_ticker_target) {
            $target = Carbon::parse($setting->countdown_ticker_target);
            if ($target->isFuture()) {
                $diff = now()->diff($target);
                $label = $setting->countdown_ticker_label ?? 'Prochain événement';
                $parts = [];
                if ($diff->d > 0) {
                    $parts[] = $diff->d.'j';
                }
                if ($diff->h > 0) {
                    $parts[] = $diff->h.'h';
                }
                $parts[] = $diff->i.'min';
                $items[] = [
                    'type' => 'countdown',
                    'text' => "{$label} dans ".implode(' ', $parts),
                    'icon' => 'ph:timer',
                ];
            }
        }

        return $items;
    }

    public function getTexts(?string $scene = null): array
    {
        return array_column($this->compile($scene), 'text');
    }
}
