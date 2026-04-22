<?php

namespace Tests\Unit;

use App\Models\TwitchToken;
use App\Services\TwitchApiClient;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class TwitchApiClientTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config()->set('twitch.client_id', 'client-id');
        config()->set('twitch.client_secret', 'client-secret');
        config()->set('twitch.helix_url', 'https://api.twitch.tv/helix');
        config()->set('twitch.token_url', 'https://id.twitch.tv/oauth2/token');
    }

    public function test_get_app_access_token_returns_cached_valid_token(): void
    {
        TwitchToken::create([
            'access_token' => 'valid-token',
            'refresh_token' => null,
            'expires_at' => now()->addHour(),
            'token_type' => 'app_access',
            'scope' => [],
        ]);
        Http::preventStrayRequests();

        $this->assertSame('valid-token', (new TwitchApiClient)->getAppAccessToken());
    }

    public function test_access_token_is_stored_encrypted_at_rest(): void
    {
        TwitchToken::create([
            'access_token' => 'super-secret-token',
            'refresh_token' => 'super-secret-refresh',
            'expires_at' => now()->addHour(),
            'token_type' => 'app_access',
            'scope' => [],
        ]);

        $raw = \DB::table('twitch_tokens')->value('access_token');
        $this->assertNotSame('super-secret-token', $raw, 'Token must be encrypted at rest');
        $this->assertNotNull(TwitchToken::current());
        $this->assertSame('super-secret-token', TwitchToken::current()->access_token);
    }

    public function test_request_new_token_when_none_exists(): void
    {
        Http::fake([
            'id.twitch.tv/*' => Http::response([
                'access_token' => 'new-token',
                'expires_in' => 3600,
                'scope' => [],
            ]),
        ]);

        $token = (new TwitchApiClient)->getAppAccessToken();

        $this->assertSame('new-token', $token);
        $this->assertDatabaseCount('twitch_tokens', 1);
    }

    public function test_request_new_token_throws_on_failure(): void
    {
        Http::fake([
            'id.twitch.tv/*' => Http::response(['message' => 'invalid credentials'], 401),
        ]);

        $this->expectException(\RuntimeException::class);
        (new TwitchApiClient)->getAppAccessToken();
    }

    public function test_create_event_sub_subscription_persists_record(): void
    {
        TwitchToken::create([
            'access_token' => 'valid-user-token',
            'refresh_token' => 'r',
            'expires_at' => now()->addHour(),
            'token_type' => 'user_access',
            'scope' => ['moderator:read:followers'],
        ]);

        Http::fake([
            'api.twitch.tv/helix/eventsub/subscriptions' => Http::response([
                'data' => [['id' => 'sub-123']],
            ], 202),
        ]);

        $result = (new TwitchApiClient)->createEventSubSubscription(
            'session-abc',
            'channel.follow',
            '2',
            ['broadcaster_user_id' => '42']
        );

        $this->assertSame('created', $result['status']);
        $this->assertDatabaseHas('twitch_event_subscriptions', [
            'subscription_id' => 'sub-123',
            'type' => 'channel.follow',
            'status' => 'enabled',
        ]);
    }

    public function test_create_event_sub_subscription_handles_409_already_exists(): void
    {
        TwitchToken::create([
            'access_token' => 'valid-user-token',
            'refresh_token' => 'r',
            'expires_at' => now()->addHour(),
            'token_type' => 'user_access',
            'scope' => [],
        ]);

        Http::fake([
            'api.twitch.tv/helix/eventsub/subscriptions' => Http::response([], 409),
        ]);

        $result = (new TwitchApiClient)->createEventSubSubscription(
            'sess',
            'channel.cheer',
            '1',
            ['broadcaster_user_id' => '42']
        );

        $this->assertSame('already_exists', $result['status']);
        $this->assertDatabaseCount('twitch_event_subscriptions', 0);
    }

    public function test_get_existing_subscriptions_returns_empty_on_failure(): void
    {
        TwitchToken::create([
            'access_token' => 'valid',
            'refresh_token' => null,
            'expires_at' => now()->addHour(),
            'token_type' => 'app_access',
            'scope' => [],
        ]);

        Http::fake([
            'api.twitch.tv/helix/eventsub/subscriptions' => Http::response(['err' => 'x'], 500),
        ]);

        $this->assertSame([], (new TwitchApiClient)->getExistingSubscriptions());
    }

    public function test_ensure_valid_user_token_throws_when_no_user_token(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessageMatches('/user-access-token/');

        (new TwitchApiClient)->ensureValidUserToken();
    }

    public function test_ensure_valid_user_token_returns_cached_valid_token(): void
    {
        TwitchToken::create([
            'access_token' => 'user-token',
            'refresh_token' => 'refresh',
            'expires_at' => now()->addHour(),
            'token_type' => 'user_access',
            'scope' => ['bits:read'],
        ]);
        Http::preventStrayRequests();

        $this->assertSame('user-token', (new TwitchApiClient)->ensureValidUserToken());
    }

    public function test_ensure_valid_user_token_refreshes_expired_token(): void
    {
        TwitchToken::create([
            'access_token' => 'stale',
            'refresh_token' => 'old-refresh',
            'expires_at' => now()->subMinute(),
            'token_type' => 'user_access',
            'scope' => ['bits:read'],
        ]);

        Http::fake([
            'id.twitch.tv/oauth2/token' => Http::response([
                'access_token' => 'refreshed',
                'refresh_token' => 'new-refresh',
                'expires_in' => 14400,
                'scope' => ['bits:read'],
            ]),
        ]);

        $token = (new TwitchApiClient)->ensureValidUserToken();

        $this->assertSame('refreshed', $token);
        // Old row replaced (Twitch rotates refresh_tokens on each use).
        $this->assertSame(1, TwitchToken::where('token_type', 'user_access')->count());
        $this->assertSame('new-refresh', TwitchToken::userAccess()->refresh_token);
    }

    public function test_ensure_valid_user_token_throws_when_refresh_fails(): void
    {
        TwitchToken::create([
            'access_token' => 'stale',
            'refresh_token' => 'bad-refresh',
            'expires_at' => now()->subMinute(),
            'token_type' => 'user_access',
            'scope' => [],
        ]);

        Http::fake([
            'id.twitch.tv/oauth2/token' => Http::response(['error' => 'invalid'], 400),
        ]);

        $this->expectException(\RuntimeException::class);
        (new TwitchApiClient)->ensureValidUserToken();
    }

    public function test_twitch_token_is_expired(): void
    {
        $token = TwitchToken::create([
            'access_token' => 't',
            'refresh_token' => null,
            'expires_at' => now()->subMinute(),
            'token_type' => 'app_access',
            'scope' => [],
        ]);

        $this->assertTrue($token->fresh()->isExpired());
    }
}
