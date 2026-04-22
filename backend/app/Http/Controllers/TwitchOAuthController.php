<?php

namespace App\Http\Controllers;

use App\Models\TwitchToken;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Twitch OAuth Authorization Code flow.
 *
 * Drives a browser through Twitch's consent screen so the app can obtain a
 * USER access token with the scopes required for EventSub WebSocket
 * subscriptions (follow, subs, bits, redemptions, hype train).
 *
 * The resulting token + refresh_token are persisted as token_type='user_access'
 * in the twitch_tokens table (encrypted at rest). Only one active user token
 * is kept at a time — previous rows are removed on each successful callback.
 */
class TwitchOAuthController extends Controller
{
    public const OAUTH_STATE_SESSION_KEY = 'twitch_oauth_state';

    /**
     * Step 1 — redirect the browser to Twitch's consent screen.
     */
    public function redirect(Request $request): RedirectResponse
    {
        $state = Str::random(40);
        $request->session()->put(self::OAUTH_STATE_SESSION_KEY, $state);

        $query = http_build_query([
            'response_type' => 'code',
            'client_id' => config('twitch.client_id'),
            'redirect_uri' => config('twitch.redirect_uri'),
            'scope' => implode(' ', config('twitch.oauth_scopes', [])),
            'state' => $state,
            'force_verify' => 'true',
        ]);

        return redirect()->away(config('twitch.authorize_url').'?'.$query);
    }

    /**
     * Step 2 — Twitch redirects back with ?code=... (or ?error=...).
     * We exchange the code for a user access token + refresh token.
     */
    public function callback(Request $request)
    {
        if ($request->filled('error')) {
            return response('Twitch authorisation error: '.$request->query('error_description', $request->query('error')), 400);
        }

        $expectedState = $request->session()->pull(self::OAUTH_STATE_SESSION_KEY);
        if (! $expectedState || ! hash_equals($expectedState, (string) $request->query('state'))) {
            return response('Invalid OAuth state — possible CSRF. Restart the flow at /twitch/oauth/redirect.', 400);
        }

        $code = (string) $request->query('code', '');
        if ($code === '') {
            return response('Missing `code` query parameter.', 400);
        }

        $response = Http::asForm()->post(config('twitch.token_url'), [
            'client_id' => config('twitch.client_id'),
            'client_secret' => config('twitch.client_secret'),
            'code' => $code,
            'grant_type' => 'authorization_code',
            'redirect_uri' => config('twitch.redirect_uri'),
        ]);

        if ($response->failed()) {
            Log::error('Twitch OAuth code exchange failed', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            return response('Failed to exchange code for token: '.$response->body(), 502);
        }

        $data = $response->json();

        // Replace any previous user token — we only need one live row.
        TwitchToken::where('token_type', 'user_access')->delete();

        TwitchToken::create([
            'access_token' => $data['access_token'],
            'refresh_token' => $data['refresh_token'] ?? null,
            'expires_at' => now()->addSeconds(($data['expires_in'] ?? 3600) - 60),
            'token_type' => 'user_access',
            'scope' => $data['scope'] ?? [],
        ]);

        return response()->json([
            'status' => 'ok',
            'message' => 'Twitch user-access-token stored. You can now start `php artisan twitch:eventsub`.',
            'scopes' => $data['scope'] ?? [],
            'expires_in' => $data['expires_in'] ?? null,
        ]);
    }
}
