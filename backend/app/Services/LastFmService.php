<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class LastFmService
{
    public function getNowPlaying(string $username, string $apiKey): ?string
    {
        try {
            $response = Http::timeout(5)->get('https://ws.audioscrobbler.com/2.0/', [
                'method' => 'user.getrecenttracks',
                'user' => $username,
                'api_key' => $apiKey,
                'format' => 'json',
                'limit' => 1,
            ]);

            if (! $response->ok()) {
                return null;
            }

            $tracks = $response->json()['recenttracks']['track'] ?? [];

            if (empty($tracks)) {
                return null;
            }

            $track = is_array($tracks[0] ?? null) ? $tracks[0] : $tracks;
            $nowPlaying = $track['@attr']['nowplaying'] ?? false;

            if (! $nowPlaying) {
                return null;
            }

            $name = $track['name'] ?? 'Inconnu';
            $artist = $track['artist']['#text'] ?? 'Inconnu';

            return "{$name} • {$artist}";
        } catch (\Throwable) {
            return null;
        }
    }
}
