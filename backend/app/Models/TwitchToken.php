<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * Persisted Twitch OAuth token.
 *
 * `access_token` and `refresh_token` are transparently encrypted at rest via
 * Eloquent's `encrypted` cast — the raw DB column holds a Laravel Crypt
 * payload (`eyJpdiI6...`). Rotating APP_KEY without re-encrypting these rows
 * makes them unreadable; see `TwitchToken::truncate()` as a recovery.
 *
 * @property int $id
 * @property string $access_token decrypted on read
 * @property string|null $refresh_token decrypted on read
 * @property Carbon $expires_at
 * @property string $token_type 'app_access' (client_credentials) or 'user_access' (authorization_code)
 * @property array<int, string>|null $scope
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
class TwitchToken extends Model
{
    protected $fillable = [
        'access_token',
        'refresh_token',
        'expires_at',
        'token_type',
        'scope',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'scope' => 'array',
        'access_token' => 'encrypted',
        'refresh_token' => 'encrypted',
    ];

    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }

    /**
     * Latest app-access-token (client_credentials grant). Used for plain Helix
     * calls that don't require a user context.
     */
    public static function current(): ?self
    {
        return static::where('token_type', 'app_access')
            ->latest()
            ->first();
    }

    /**
     * Latest user-access-token (authorization_code grant). Required for
     * EventSub WebSocket subscriptions. Null until the broadcaster completes
     * the OAuth consent flow at /twitch/oauth/redirect.
     */
    public static function userAccess(): ?self
    {
        return static::where('token_type', 'user_access')
            ->latest()
            ->first();
    }
}
