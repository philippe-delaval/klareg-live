<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class TwitchChatMessage implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public string $username,
        public string $message,
        public array $badges = [],
        public ?string $color = null,
    ) {}

    public function broadcastOn(): array
    {
        return [new Channel('twitch-chat')];
    }

    public function broadcastWith(): array
    {
        return [
            'username' => $this->username,
            'message' => $this->message,
            'badges' => $this->badges,
            'color' => $this->color,
        ];
    }

    public function broadcastAs(): string
    {
        return 'twitch-chat-message';
    }
}
