<?php

namespace Tests\Feature;

use App\Models\OverlaySetting;
use App\Models\Schedule;
use App\Models\TwitchToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class OverlayApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_settings_endpoint_returns_overlay_payload(): void
    {
        OverlaySetting::create(array_merge(
            OverlaySetting::defaults(),
            ['channel_name' => 'TestChannel', 'sub_goal' => 500, 'sub_current' => 120]
        ));

        $response = $this->getJson('/api/overlay/settings');

        $response->assertOk()
            ->assertJsonStructure([
                'channel_name',
                'starting_title',
                'brb_message',
                'accent_color',
                'sub_goal',
                'sub_current',
                'socials',
                'ticker_messages',
            ])
            ->assertJson([
                'channel_name' => 'TestChannel',
                'sub_goal' => 500,
                'sub_current' => 120,
            ]);
    }

    public function test_settings_endpoint_creates_defaults_when_empty(): void
    {
        $this->assertSame(0, OverlaySetting::count());

        $response = $this->getJson('/api/overlay/settings');

        $response->assertOk();
        $this->assertSame(1, OverlaySetting::count());
    }

    public function test_settings_cache_is_busted_when_overlay_setting_is_updated(): void
    {
        $setting = OverlaySetting::create(array_merge(
            OverlaySetting::defaults(),
            ['channel_name' => 'Before']
        ));

        $this->getJson('/api/overlay/settings')->assertJson(['channel_name' => 'Before']);

        $setting->update(['channel_name' => 'After']);

        $this->getJson('/api/overlay/settings')->assertJson(['channel_name' => 'After']);
    }

    public function test_schedule_endpoint_returns_active_entries_in_order(): void
    {
        Schedule::create(['time' => '20:00', 'label' => 'Later', 'is_active' => true, 'sort_order' => 2]);
        Schedule::create(['time' => '18:00', 'label' => 'First', 'is_active' => true, 'sort_order' => 1]);
        Schedule::create(['time' => '22:00', 'label' => 'Hidden', 'is_active' => false, 'sort_order' => 3]);

        $response = $this->getJson('/api/overlay/schedule');

        $response->assertOk()
            ->assertJsonCount(2)
            ->assertJsonPath('0.label', 'First')
            ->assertJsonPath('1.label', 'Later');
    }

    public function test_reverb_config_endpoint_exposes_only_public_fields(): void
    {
        config()->set('broadcasting.connections.reverb.key', 'public-key');
        config()->set('broadcasting.connections.reverb.secret', 'super-secret');
        config()->set('broadcasting.connections.reverb.options.host', 'ws.example.com');
        config()->set('broadcasting.connections.reverb.options.port', 8080);
        config()->set('broadcasting.connections.reverb.options.scheme', 'https');

        $response = $this->getJson('/api/overlay/reverb-config');

        $response->assertOk()
            ->assertExactJson([
                'key' => 'public-key',
                'host' => 'ws.example.com',
                'port' => 8080,
                'scheme' => 'https',
            ]);

        $this->assertStringNotContainsString('super-secret', $response->getContent());
    }

    public function test_twitch_status_endpoint_reports_token_validity(): void
    {
        TwitchToken::create([
            'access_token' => 'plain-text-token',
            'refresh_token' => null,
            'expires_at' => now()->addHour(),
            'token_type' => 'app_access',
            'scope' => [],
        ]);
        Cache::put('twitch:eventsub:status', 'connected');

        $response = $this->getJson('/api/twitch/status');

        $response->assertOk()
            ->assertJsonPath('eventsub.status', 'connected')
            ->assertJsonPath('token.is_valid', true);
    }

    public function test_health_endpoint_returns_ok_when_dependencies_are_up(): void
    {
        $response = $this->getJson('/api/health');

        $response->assertOk()
            ->assertJsonPath('status', 'ok')
            ->assertJsonPath('checks.database', true)
            ->assertJsonPath('checks.cache', true);
    }

    public function test_twitch_status_reports_invalid_when_token_expired(): void
    {
        TwitchToken::create([
            'access_token' => 'expired',
            'refresh_token' => null,
            'expires_at' => now()->subMinute(),
            'token_type' => 'app_access',
            'scope' => [],
        ]);

        $response = $this->getJson('/api/twitch/status');

        $response->assertOk()
            ->assertJsonPath('token.is_valid', false);
    }
}
