<?php

namespace App\Console\Commands;

use App\Console\Concerns\ValidatesTwitchConfig;
use App\Services\TwitchApiClient;
use Illuminate\Console\Command;

class TwitchRefreshTokenCommand extends Command
{
    use ValidatesTwitchConfig;

    protected $signature = 'twitch:refresh-token';

    protected $description = 'Refresh the Twitch app access token if expired or near expiry';

    public function handle(): int
    {
        if (! $this->requireTwitchCredentials()) {
            return self::FAILURE;
        }

        $api = app(TwitchApiClient::class);
        $token = $api->ensureValidToken();

        $this->info('Twitch app access token is valid.');

        return self::SUCCESS;
    }
}
