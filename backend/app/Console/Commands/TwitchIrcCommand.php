<?php

namespace App\Console\Commands;

use App\Events\TwitchChatMessage;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Ratchet\Client\Connector;
use React\EventLoop\Loop;

class TwitchIrcCommand extends Command
{
    protected $signature = 'twitch:irc';

    protected $description = 'Connect to Twitch IRC and broadcast chat messages';

    private int $reconnectAttempts = 0;

    private int $maxReconnect = 20;

    private int $baseDelay = 1000;

    public function handle(): int
    {
        $channel = config('twitch.channel_name');

        if (! $channel) {
            $this->error('TWITCH_CHANNEL_NAME must be set in .env');

            return self::FAILURE;
        }

        $this->info("Starting Twitch IRC listener for #{$channel}...");
        $this->connect($channel);

        return self::SUCCESS;
    }

    private function connect(string $channel): void
    {
        $loop = Loop::get();
        $connector = new Connector($loop);

        $connector(config('twitch.irc_url'))->then(function ($conn) use ($channel) {
            $this->reconnectAttempts = 0;
            $this->info('Connected to Twitch IRC WebSocket');
            Cache::put('twitch:irc:status', 'connected', 60);
            Cache::put('twitch:irc:connected_at', now()->toISOString(), 60);

            // IRC handshake
            $conn->send('CAP REQ :twitch.tv/tags twitch.tv/commands');
            $conn->send('PASS oauth:justinfan');
            $conn->send('NICK justinfan'.random_int(10000, 99999));
            $conn->send('JOIN #'.strtolower($channel));

            $conn->on('message', function ($msg) use ($conn) {
                $lines = explode("\r\n", (string) $msg);
                foreach ($lines as $line) {
                    $line = trim($line);
                    if (empty($line)) {
                        continue;
                    }

                    if (str_starts_with($line, 'PING')) {
                        $conn->send('PONG :'.explode(':', $line, 2)[1] ?? '');

                        continue;
                    }

                    if (str_contains($line, 'PRIVMSG')) {
                        $this->parseAndBroadcast($line);
                    }
                }
            });

            $conn->on('close', function () use ($channel) {
                Cache::forget('twitch:irc:status');
                Cache::forget('twitch:irc:connected_at');
                $this->warn('IRC WebSocket connection closed');
                $this->reconnect($channel);
            });

            $conn->on('error', function (\Exception $e) use ($channel) {
                Cache::forget('twitch:irc:status');
                $this->error("IRC WebSocket error: {$e->getMessage()}");
                $this->reconnect($channel);
            });
        }, function (\Exception $e) use ($channel) {
            Cache::forget('twitch:irc:status');
            $this->error("IRC connection failed: {$e->getMessage()}");
            $this->reconnect($channel);
        });

        $loop->run();
    }

    private function reconnect(string $channel): void
    {
        if ($this->reconnectAttempts >= $this->maxReconnect) {
            $this->error('Max reconnection attempts reached. Exiting.');

            return;
        }

        $this->reconnectAttempts++;
        $delay = $this->baseDelay * $this->reconnectAttempts;
        $this->info("Reconnecting in {$delay}ms (attempt {$this->reconnectAttempts})...");

        usleep($delay * 1000);
        $this->connect($channel);
    }

    private function parseAndBroadcast(string $line): void
    {
        try {
            // Parse IRC tags for badges and color
            $badges = [];
            if (preg_match('/@badges=([^;\s]+)/', $line, $m)) {
                $badgeStr = $m[1];
                if (str_contains($badgeStr, 'moderator')) {
                    $badges[] = 'mod';
                }
                if (str_contains($badgeStr, 'vip')) {
                    $badges[] = 'vip';
                }
                if (str_contains($badgeStr, 'subscriber')) {
                    $badges[] = 'sub';
                }
                if (str_contains($badgeStr, 'bits')) {
                    $badges[] = 'bits';
                }
            }

            $color = null;
            if (preg_match('/color=(#[0-9A-Fa-f]{6})/', $line, $m)) {
                $color = $m[1];
            }

            $username = 'Unknown';
            if (preg_match('/display-name=([^;\s]+)/', $line, $m)) {
                $username = $m[1];
            }

            $message = '';
            if (preg_match('/PRIVMSG #[^ ]+ :(.+)/', $line, $m)) {
                $message = trim($m[1]);
            }

            if ($username && $message) {
                event(new TwitchChatMessage($username, $message, $badges, $color));
            }
        } catch (\Throwable $e) {
            Log::debug('IRC message parse error', [
                'error' => $e->getMessage(),
                'line' => mb_substr($line, 0, 200),
            ]);
        }
    }
}
