<?php

namespace App\Console\Concerns;

trait ValidatesTwitchConfig
{
    /**
     * Ensure TWITCH_CLIENT_ID and TWITCH_CLIENT_SECRET are both set.
     * Returns true on success; prints an error and returns false otherwise.
     */
    protected function requireTwitchCredentials(): bool
    {
        if (! config('twitch.client_id') || ! config('twitch.client_secret')) {
            $this->error('TWITCH_CLIENT_ID and TWITCH_CLIENT_SECRET must be set in .env');

            return false;
        }

        return true;
    }
}
