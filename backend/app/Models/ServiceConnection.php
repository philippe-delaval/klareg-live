<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ServiceConnection extends Model
{
    protected $fillable = [
        'spotify_enabled',
        'spotify_client_id',
        'spotify_client_secret',
        'spotify_refresh_token',
        'spotify_connected_at',
        'weather_enabled',
        'weather_api_key',
        'weather_city',
        'weather_units',
        'coingecko_api_key',
    ];

    protected $casts = [
        'spotify_enabled' => 'boolean',
        'spotify_connected_at' => 'datetime',
        'weather_enabled' => 'boolean',
    ];

    public static function current(): self
    {
        return self::firstOrCreate([], [
            'spotify_enabled' => false,
            'weather_enabled' => false,
            'weather_city' => 'Paris',
            'weather_units' => 'metric',
        ]);
    }

    public function isSpotifyConnected(): bool
    {
        return ! empty($this->spotify_refresh_token);
    }
}
