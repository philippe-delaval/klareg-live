<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class SpotifyService
{
    private function getAccessToken(string $clientId, string $clientSecret, string $refreshToken): ?string
    {
        $cacheKey = 'spotify:access_token:'.md5($refreshToken);

        return Cache::remember($cacheKey, 3300, function () use ($clientId, $clientSecret, $refreshToken) {
            $response = Http::timeout(10)
                ->asForm()
                ->withBasicAuth($clientId, $clientSecret)
                ->post('https://accounts.spotify.com/api/token', [
                    'grant_type' => 'refresh_token',
                    'refresh_token' => $refreshToken,
                ]);

            if (! $response->ok()) {
                return null;
            }

            return $response->json('access_token');
        });
    }

    public function getNowPlaying(string $clientId, string $clientSecret, string $refreshToken): ?string
    {
        try {
            $accessToken = $this->getAccessToken($clientId, $clientSecret, $refreshToken);

            if (! $accessToken) {
                return null;
            }

            $response = Http::timeout(5)
                ->withToken($accessToken)
                ->get('https://api.spotify.com/v1/me/player/currently-playing');

            if ($response->status() === 204 || ! $response->ok()) {
                return null;
            }

            $data = $response->json();

            if (($data['is_playing'] ?? false) === false) {
                return null;
            }

            $track = $data['item']['name'] ?? null;
            $artist = collect($data['item']['artists'] ?? [])->pluck('name')->implode(', ');

            if (! $track) {
                return null;
            }

            return "{$track} • {$artist}";
        } catch (\Throwable) {
            return null;
        }
    }

    public static function getAuthUrl(string $clientId, string $redirectUri): string
    {
        $params = http_build_query([
            'client_id' => $clientId,
            'response_type' => 'code',
            'redirect_uri' => $redirectUri,
            'scope' => 'user-read-currently-playing user-read-playback-state',
        ]);

        return 'https://accounts.spotify.com/authorize?'.$params;
    }

    public static function exchangeCode(string $code, string $clientId, string $clientSecret, string $redirectUri): ?string
    {
        try {
            $response = Http::timeout(10)
                ->asForm()
                ->withBasicAuth($clientId, $clientSecret)
                ->post('https://accounts.spotify.com/api/token', [
                    'grant_type' => 'authorization_code',
                    'code' => $code,
                    'redirect_uri' => $redirectUri,
                ]);

            if (! $response->ok()) {
                return null;
            }

            return $response->json('refresh_token');
        } catch (\Throwable) {
            return null;
        }
    }
}
