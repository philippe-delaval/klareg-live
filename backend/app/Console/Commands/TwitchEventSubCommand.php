<?php

namespace App\Console\Commands;

use App\Console\Concerns\ValidatesTwitchConfig;
use App\Events\TwitchEventReceived;
use App\Models\TwitchToken;
use App\Services\TwitchApiClient;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Ratchet\Client\Connector;
use React\EventLoop\Loop;

class TwitchEventSubCommand extends Command
{
    use ValidatesTwitchConfig;

    protected $signature = 'twitch:eventsub';

    protected $description = 'Connect to Twitch EventSub WebSocket and broadcast events';

    private int $reconnectAttempts = 0;

    private int $maxReconnect = 20;

    private int $baseDelay = 1000;

    public function handle(): int
    {
        if (! $this->requireTwitchCredentials()) {
            return self::FAILURE;
        }

        if (! TwitchToken::userAccess()) {
            $this->error('No Twitch user-access-token found.');
            $this->line('Open this URL in your browser to authorise the app:');
            $this->line('  '.url('/twitch/oauth/redirect'));
            $this->line('Then run this command again.');

            return self::FAILURE;
        }

        $this->info('Starting Twitch EventSub listener...');
        $this->connect();

        return self::SUCCESS;
    }

    private function connect(): void
    {
        $loop = Loop::get();
        $connector = new Connector($loop);

        $connector(config('twitch.eventsub_url'))->then(
            fn ($conn) => $this->onConnected($conn),
            fn (\Exception $e) => $this->onConnectionFailed($e),
        );

        $loop->run();
    }

    private function onConnected($conn): void
    {
        $this->reconnectAttempts = 0;
        $this->info('Connected to Twitch EventSub WebSocket');
        Cache::put('twitch:eventsub:status', 'connected', 60);
        Cache::put('twitch:eventsub:connected_at', now()->toISOString(), 60);

        $conn->on('message', fn ($msg) => $this->onMessage($msg, $conn));
        $conn->on('close', fn () => $this->onClose());
        $conn->on('error', fn (\Exception $e) => $this->onError($e));
    }

    private function onMessage($msg, $conn): void
    {
        $data = json_decode((string) $msg, true);
        if (! is_array($data)) {
            return;
        }

        $messageType = $data['metadata']['message_type'] ?? '';

        match ($messageType) {
            'session_welcome' => $this->handleSessionWelcome($data),
            'notification' => $this->handleNotification($data),
            'session_keepalive' => $this->handleKeepalive(),
            'session_reconnect' => $this->handleSessionReconnect($data, $conn),
            'session_revoked' => $this->handleSessionRevoked($data, $conn),
            default => $this->line("Unknown message type: {$messageType}"),
        };
    }

    private function handleSessionWelcome(array $data): void
    {
        $sessionId = $data['payload']['session']['id'] ?? null;
        if (! $sessionId) {
            $this->warn('session_welcome missing session id');

            return;
        }
        $this->info("Session welcome received. ID: {$sessionId}");
        $this->subscribeToEvents($sessionId);
    }

    private function handleNotification(array $data): void
    {
        $subType = $data['payload']['subscription']['type'] ?? 'unknown';
        $this->line("Notification: {$subType}");
        event(new TwitchEventReceived($subType, $data['payload']['event'] ?? []));
    }

    private function handleKeepalive(): void
    {
        Cache::put('twitch:eventsub:status', 'connected', 60);
        $this->line('Keepalive');
    }

    private function handleSessionReconnect(array $data, $conn): void
    {
        $reconnectUrl = $data['payload']['session']['reconnect_url'] ?? null;
        $this->warn("Reconnect requested. URL: {$reconnectUrl}");
        $conn->close();
        $reconnectUrl ? $this->reconnectToUrl($reconnectUrl) : $this->reconnect();
    }

    private function handleSessionRevoked(array $data, $conn): void
    {
        $this->error('Session revoked: '.json_encode($data['payload'] ?? []));
        $conn->close();
        $this->reconnect();
    }

    private function onClose(): void
    {
        Cache::forget('twitch:eventsub:status');
        Cache::forget('twitch:eventsub:connected_at');
        $this->warn('WebSocket connection closed');
        $this->reconnect();
    }

    private function onError(\Exception $e): void
    {
        Cache::forget('twitch:eventsub:status');
        $this->error("WebSocket error: {$e->getMessage()}");
        $this->reconnect();
    }

    private function onConnectionFailed(\Exception $e): void
    {
        Cache::forget('twitch:eventsub:status');
        $this->error("Connection failed: {$e->getMessage()}");
        $this->reconnect();
    }

    private function reconnectToUrl(string $url): void
    {
        $this->info('Reconnecting to new URL...');
        // Override the EventSub URL temporarily and reconnect
        config(['twitch.eventsub_url' => $url]);
        $this->connect();
    }

    private function reconnect(): void
    {
        if ($this->reconnectAttempts >= $this->maxReconnect) {
            $this->error('Max reconnection attempts reached. Exiting.');

            return;
        }

        $this->reconnectAttempts++;
        $delay = $this->baseDelay * $this->reconnectAttempts;
        $this->info("Reconnecting in {$delay}ms (attempt {$this->reconnectAttempts})...");

        usleep($delay * 1000);
        $this->connect();
    }

    private function subscribeToEvents(string $sessionId): void
    {
        $api = app(TwitchApiClient::class);
        $channelId = config('twitch.channel_id');

        if (! $channelId) {
            $this->warn('TWITCH_CHANNEL_ID not set. Attempting to resolve from channel name...');
            $channelId = $api->getUserIdFromUsername(config('twitch.channel_name'));

            if ($channelId) {
                config(['twitch.channel_id' => $channelId]);
                $this->info("Resolved channel ID: {$channelId}");
            } else {
                $this->error('Could not resolve channel ID. Set TWITCH_CHANNEL_ID in .env');

                return;
            }
        }

        foreach (config('twitch.event_types') as $eventConfig) {
            $type = $eventConfig['type'];
            $version = $eventConfig['version'];

            $condition = $this->buildCondition($type, $channelId);

            $this->info("Subscribing to: {$type}");
            $result = $api->createEventSubSubscription($sessionId, $type, $version, $condition);
            $this->line("  → {$result['status']}");
        }
    }

    private function buildCondition(string $type, string $channelId): array
    {
        $moderatorCondition = ['broadcaster_user_id' => $channelId, 'moderator_user_id' => $channelId];
        $broadcasterCondition = ['broadcaster_user_id' => $channelId];
        $toCondition = ['to_broadcaster_user_id' => $channelId];

        return match ($type) {
            'channel.follow' => $moderatorCondition,
            'channel.raid' => $toCondition,
            default => $broadcasterCondition,
        };
    }
}
