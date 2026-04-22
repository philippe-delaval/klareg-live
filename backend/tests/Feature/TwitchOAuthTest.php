<?php

namespace Tests\Feature;

use App\Http\Controllers\TwitchOAuthController;
use App\Models\TwitchToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class TwitchOAuthTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config()->set('twitch.client_id', 'client-id');
        config()->set('twitch.client_secret', 'client-secret');
        config()->set('twitch.authorize_url', 'https://id.twitch.tv/oauth2/authorize');
        config()->set('twitch.token_url', 'https://id.twitch.tv/oauth2/token');
        config()->set('twitch.redirect_uri', 'http://localhost/twitch/oauth/callback');
        config()->set('twitch.oauth_scopes', ['moderator:read:followers', 'bits:read']);
    }

    public function test_redirect_sends_browser_to_twitch_with_scopes_and_state(): void
    {
        $response = $this->get('/twitch/oauth/redirect');

        $response->assertRedirect();
        $location = $response->headers->get('Location');

        $this->assertStringStartsWith('https://id.twitch.tv/oauth2/authorize?', $location);
        $this->assertStringContainsString('client_id=client-id', $location);
        $this->assertStringContainsString('response_type=code', $location);
        $this->assertStringContainsString('scope=moderator%3Aread%3Afollowers+bits%3Aread', $location);
        $this->assertStringContainsString('redirect_uri=http%3A%2F%2Flocalhost%2Ftwitch%2Foauth%2Fcallback', $location);
        $this->assertNotEmpty(session(TwitchOAuthController::OAUTH_STATE_SESSION_KEY));
    }

    public function test_callback_rejects_missing_state(): void
    {
        $response = $this->get('/twitch/oauth/callback?code=abc&state=forged');

        $response->assertStatus(400);
        $this->assertSame(0, TwitchToken::count());
    }

    public function test_callback_rejects_error_from_twitch(): void
    {
        $response = $this->get('/twitch/oauth/callback?error=access_denied&error_description=User+denied');

        $response->assertStatus(400);
        $response->assertSeeText('User denied');
    }

    public function test_callback_exchanges_code_and_stores_user_access_token(): void
    {
        Http::fake([
            'id.twitch.tv/oauth2/token' => Http::response([
                'access_token' => 'user-token-xyz',
                'refresh_token' => 'refresh-xyz',
                'expires_in' => 14400,
                'scope' => ['moderator:read:followers', 'bits:read'],
                'token_type' => 'bearer',
            ]),
        ]);

        $this->withSession([TwitchOAuthController::OAUTH_STATE_SESSION_KEY => 'known-state']);

        $response = $this->get('/twitch/oauth/callback?code=auth-code&state=known-state');

        $response->assertOk()
            ->assertJsonPath('status', 'ok');

        $stored = TwitchToken::userAccess();
        $this->assertNotNull($stored);
        $this->assertSame('user-token-xyz', $stored->access_token);
        $this->assertSame('refresh-xyz', $stored->refresh_token);
        $this->assertEqualsWithDelta(now()->addSeconds(14400 - 60)->timestamp, $stored->expires_at->timestamp, 2);
    }

    public function test_callback_replaces_previous_user_token(): void
    {
        TwitchToken::create([
            'access_token' => 'old',
            'refresh_token' => 'old-refresh',
            'expires_at' => now()->addHour(),
            'token_type' => 'user_access',
            'scope' => [],
        ]);

        Http::fake([
            'id.twitch.tv/oauth2/token' => Http::response([
                'access_token' => 'fresh',
                'refresh_token' => 'fresh-refresh',
                'expires_in' => 14400,
                'scope' => [],
            ]),
        ]);

        $this->withSession([TwitchOAuthController::OAUTH_STATE_SESSION_KEY => 's']);
        $this->get('/twitch/oauth/callback?code=c&state=s')->assertOk();

        $this->assertSame(1, TwitchToken::where('token_type', 'user_access')->count());
        $this->assertSame('fresh', TwitchToken::userAccess()->access_token);
    }
}
