<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class WeatherService
{
    public function getFormatted(string $city, string $apiKey, string $units = 'metric'): ?string
    {
        try {
            $response = Http::timeout(5)->get('https://api.openweathermap.org/data/2.5/weather', [
                'q' => $city,
                'appid' => $apiKey,
                'units' => $units,
                'lang' => 'fr',
            ]);

            if (! $response->ok()) {
                return null;
            }

            $data = $response->json();
            $temp = round($data['main']['temp'] ?? 0);
            $desc = ucfirst($data['weather'][0]['description'] ?? '');
            $unit = $units === 'imperial' ? '°F' : '°C';

            return "{$city} • {$temp}{$unit} • {$desc}";
        } catch (\Throwable) {
            return null;
        }
    }
}
