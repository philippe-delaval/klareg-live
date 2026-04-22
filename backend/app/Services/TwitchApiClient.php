<?php

namespace App\Services;

use App\Models\TwitchEventSubscription;
use App\Models\TwitchToken;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Thin wrapper around Twitch's Helix + OAuth endpoints.
 *
 * Uses app-access-tokens (client_credentials grant) — suitable for server-side
 * operations that don't act on behalf of a specific user. Tokens are persisted
 * and reused across processes via the {@see TwitchToken} model (encrypted at
 * rest). Requires TWITCH_CLIENT_ID and TWITCH_CLIENT_SECRET in config.
 */
class TwitchApiClient
{
    /**
     * Return a cached valid app-access-token if available, or request a new one.
     *
     * @return string The bearer token to pass as "Authorization: Bearer {token}".
     *
     * @throws \RuntimeException When a token request is needed and Twitch rejects it.
     */
    public function getAppAccessToken(): string
    {
        $token = TwitchToken::current();

        if ($token && ! $token->isExpired()) {
            return $token->access_token;
        }

        return $this->requestNewToken();
    }

    /**
     * Same as {@see getAppAccessToken()} but always refreshes a missing/expired token.
     *
     * Callers use this when they're about to make a Helix request and want to
     * minimise the chance of hitting Twitch with a stale token.
     *
     * @throws \RuntimeException When the refresh fails.
     */
    public function ensureValidToken(): string
    {
        $token = TwitchToken::current();

        if (! $token || $token->isExpired()) {
            return $this->requestNewToken();
        }

        return $token->access_token;
    }

    /**
     * Create an EventSub subscription bound to a running WebSocket session.
     *
     * Persists a {@see TwitchEventSubscription} record on success so that
     * subsequent cleanups can reconcile local state with Twitch's.
     *
     * @param  string  $sessionId  WebSocket session id received in `session_welcome`.
     * @param  string  $type  Twitch subscription type (e.g. `channel.follow`).
     * @param  string  $version  Subscription schema version (e.g. `2`).
     * @param  array<string, string>  $condition  Broadcaster/moderator IDs, etc.
     * @return array{status: 'created'|'already_exists'|'error', code?: int, data?: array<string, mixed>}
     */
    public function createEventSubSubscription(string $sessionId, string $type, string $version, array $condition): array
    {
        // EventSub via WebSocket transport REQUIRES a user-access-token — the
        // app-access-token only works for webhook transport. See
        // https://dev.twitch.tv/docs/eventsub/handling-websocket-events/
        $response = Http::withHeaders([
            'Client-Id' => config('twitch.client_id'),
            'Authorization' => 'Bearer '.$this->ensureValidUserToken(),
        ])->post(config('twitch.helix_url').'/eventsub/subscriptions', [
            'type' => $type,
            'version' => $version,
            'condition' => $condition,
            'transport' => [
                'method' => 'websocket',
                'session_id' => $sessionId,
            ],
        ]);

        if ($response->status() === 409) {
            Log::info("Twitch EventSub subscription already exists: {$type}");

            return ['status' => 'already_exists'];
        }

        if ($response->failed()) {
            Log::error('Twitch EventSub subscription failed', [
                'type' => $type,
                'status' => $response->status(),
                'body' => $response->json(),
            ]);

            return ['status' => 'error', 'code' => $response->status()];
        }

        $data = $response->json('data.0', []);
        Log::info("Twitch EventSub subscription created: {$type}", ['id' => $data['id'] ?? null]);

        TwitchEventSubscription::updateOrCreate(
            ['subscription_id' => $data['id'] ?? uniqid()],
            [
                'type' => $type,
                'version' => $version,
                'condition' => $condition,
                'status' => 'enabled',
                'session_id' => $sessionId,
            ]
        );

        return ['status' => 'created', 'data' => $data];
    }

    /**
     * List all EventSub subscriptions currently known to Twitch for this app.
     *
     * Returns an empty array when Twitch responds with an error — callers must
     * not treat "empty list" as "no subscriptions ever existed". Errors are
     * logged upstream; this method is deliberately lenient to avoid breaking
     * reconciliation jobs on transient 5xx.
     *
     * @return array<int, array<string, mixed>>
     */
    public function getExistingSubscriptions(): array
    {
        $response = Http::withHeaders([
            'Client-Id' => config('twitch.client_id'),
            'Authorization' => 'Bearer '.$this->ensureValidToken(),
        ])->get(config('twitch.helix_url').'/eventsub/subscriptions');

        if ($response->failed()) {
            return [];
        }

        return $response->json('data', []);
    }

    /**
     * Delete a remote EventSub subscription by id. Local rows are not touched —
     * use {@see TwitchCleanupSubscriptionsCommand} to reconcile.
     */
    public function deleteEventSubSubscription(string $subscriptionId): bool
    {
        $response = Http::withHeaders([
            'Client-Id' => config('twitch.client_id'),
            'Authorization' => 'Bearer '.$this->ensureValidToken(),
        ])->delete(config('twitch.helix_url')."/eventsub/subscriptions?id={$subscriptionId}");

        return $response->successful();
    }

    /**
     * Resolve a Twitch username (login) to its numeric channel id.
     *
     * Returns null if Twitch reports no such user — do NOT call this on hot
     * paths; cache the result in config/.env after first resolution.
     */
    public function getUserIdFromUsername(string $username): ?string
    {
        $response = Http::withHeaders([
            'Client-Id' => config('twitch.client_id'),
            'Authorization' => 'Bearer '.$this->ensureValidToken(),
        ])->get(config('twitch.helix_url').'/users', ['login' => $username]);

        return $response->json('data.0.id');
    }

    /**
     * Fetch the current `/helix/streams` row for a channel, or null if offline.
     *
     * @return array<string, mixed>|null
     */
    public function getStreamInfo(string $channelId): ?array
    {
        $response = Http::withHeaders([
            'Client-Id' => config('twitch.client_id'),
            'Authorization' => 'Bearer '.$this->ensureValidToken(),
        ])->get(config('twitch.helix_url').'/streams', ['user_id' => $channelId]);

        return $response->json('data.0');
    }

    /**
     * Return a valid user-access-token, refreshing it via the stored
     * refresh_token if needed.
     *
     * @throws \RuntimeException If no user token exists (broadcaster must run
     *                           the OAuth flow at /twitch/oauth/redirect first) or if the refresh fails.
     */
    public function ensureValidUserToken(): string
    {
        $token = TwitchToken::userAccess();

        if (! $token) {
            throw new \RuntimeException(
                'No Twitch user-access-token found. Open '.url('/twitch/oauth/redirect').' in your browser to authorise the app.'
            );
        }

        if (! $token->isExpired()) {
            return $token->access_token;
        }

        if (! $token->refresh_token) {
            throw new \RuntimeException(
                'User-access-token expired and no refresh_token stored. Re-authorise at /twitch/oauth/redirect.'
            );
        }

        return $this->refreshUserToken($token);
    }

    /**
     * Exchange a refresh_token for a fresh user-access-token and persist it.
     */
    protected function refreshUserToken(TwitchToken $expired): string
    {
        $response = Http::asForm()->post(config('twitch.token_url'), [
            'client_id' => config('twitch.client_id'),
            'client_secret' => config('twitch.client_secret'),
            'grant_type' => 'refresh_token',
            'refresh_token' => $expired->refresh_token,
        ]);

        if ($response->failed()) {
            Log::error('Twitch user token refresh failed', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
            throw new \RuntimeException('Failed to refresh Twitch user-access-token — re-authorise at /twitch/oauth/redirect.');
        }

        $data = $response->json();

        // Replace the existing row — Twitch rotates the refresh_token on each use.
        TwitchToken::where('token_type', 'user_access')->delete();

        $new = TwitchToken::create([
            'access_token' => $data['access_token'],
            'refresh_token' => $data['refresh_token'] ?? null,
            'expires_at' => now()->addSeconds(($data['expires_in'] ?? 3600) - 60),
            'token_type' => 'user_access',
            'scope' => $data['scope'] ?? [],
        ]);

        return $new->access_token;
    }

    protected function requestNewToken(): string
    {
        $response = Http::asForm()->post(config('twitch.token_url'), [
            'client_id' => config('twitch.client_id'),
            'client_secret' => config('twitch.client_secret'),
            'grant_type' => 'client_credentials',
        ]);

        if ($response->failed()) {
            Log::error('Twitch token request failed', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
            throw new \RuntimeException('Failed to obtain Twitch app access token');
        }

        $data = $response->json();

        TwitchToken::create([
            'access_token' => $data['access_token'],
            'refresh_token' => $data['refresh_token'] ?? null,
            'expires_at' => now()->addSeconds($data['expires_in'] - 60),
            'token_type' => 'app_access',
            'scope' => $data['scope'] ?? [],
        ]);

        return $data['access_token'];
    }
}
