<?php

namespace App\Http\Controllers;

use App\Filament\Resources\ServiceConnections\ServiceConnectionResource;
use App\Models\ServiceConnection;
use App\Services\SpotifyService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class SpotifyController extends Controller
{
    public function redirect(): RedirectResponse
    {
        $setting = ServiceConnection::current();

        if (! $setting->spotify_client_id) {
            return redirect(ServiceConnectionResource::getUrl('edit', ['record' => $setting]))
                ->with('error', 'Configure d\'abord le Client ID Spotify dans les paramètres.');
        }

        $url = SpotifyService::getAuthUrl(
            $setting->spotify_client_id,
            $this->redirectUri()
        );

        return redirect($url);
    }

    public function callback(Request $request): RedirectResponse
    {
        $adminUrl = ServiceConnectionResource::getUrl('edit', ['record' => ServiceConnection::current()]);

        if ($request->has('error')) {
            return redirect($adminUrl)->with('error', 'Connexion Spotify annulée.');
        }

        $setting = ServiceConnection::current();

        $refreshToken = SpotifyService::exchangeCode(
            $request->get('code'),
            $setting->spotify_client_id,
            $setting->spotify_client_secret,
            $this->redirectUri()
        );

        if (! $refreshToken) {
            return redirect($adminUrl)->with('error', 'Échec de la connexion Spotify.');
        }

        $setting->update([
            'spotify_refresh_token' => $refreshToken,
            'spotify_connected_at' => now(),
        ]);

        Cache::forget('spotify:access_token:'.md5($setting->spotify_refresh_token ?? ''));

        return redirect($adminUrl)->with('success', 'Spotify connecté avec succès !');
    }

    private function redirectUri(): string
    {
        return config('app.url').'/spotify/callback';
    }
}
