<?php

namespace App\Console\Commands;

use App\Console\Concerns\ValidatesTwitchConfig;
use App\Models\TwitchEventSubscription;
use App\Services\TwitchApiClient;
use Illuminate\Console\Command;

class TwitchCleanupSubscriptionsCommand extends Command
{
    use ValidatesTwitchConfig;

    protected $signature = 'twitch:cleanup-subscriptions';

    protected $description = 'Clean up orphaned EventSub subscriptions on Twitch';

    public function handle(): int
    {
        if (! $this->requireTwitchCredentials()) {
            return self::FAILURE;
        }

        $api = app(TwitchApiClient::class);
        $remoteSubs = $api->getExistingSubscriptions();

        $this->info('Found '.count($remoteSubs).' remote subscriptions');

        $remoteIds = collect($remoteSubs)->pluck('id')->filter()->values()->all();

        // Mark orphaned local entries removed in a single UPDATE.
        $orphanedLocal = TwitchEventSubscription::whereNotIn('subscription_id', $remoteIds ?: [''])
            ->where('status', '!=', 'removed');
        $orphanedLocalCount = (clone $orphanedLocal)->count();
        if ($orphanedLocalCount > 0) {
            $this->line("Marking {$orphanedLocalCount} orphaned local subscription(s) as removed");
            $orphanedLocal->update(['status' => 'removed']);
        }

        // Fetch known remote IDs from DB once, then delete the diff remotely.
        $knownLocalIds = TwitchEventSubscription::whereIn('subscription_id', $remoteIds ?: [''])
            ->pluck('subscription_id')
            ->all();
        $unknownRemote = array_diff($remoteIds, $knownLocalIds);
        foreach ($remoteSubs as $remote) {
            if (in_array($remote['id'] ?? null, $unknownRemote, true)) {
                $this->line("Deleting orphaned remote subscription: {$remote['id']} ({$remote['type']})");
                $api->deleteEventSubSubscription($remote['id']);
            }
        }

        $this->info('Cleanup complete.');

        return self::SUCCESS;
    }
}
