<?php

namespace App\Observers;

use App\Events\BroadcastOverlayUpdate;
use App\Http\Controllers\OverlayApiController;
use App\Models\OverlaySetting;
use Illuminate\Broadcasting\BroadcastException;
use Illuminate\Support\Facades\Cache;

class OverlaySettingObserver
{
    public function saved(OverlaySetting $setting): void
    {
        Cache::forget(OverlayApiController::SETTINGS_CACHE_KEY);

        try {
            BroadcastOverlayUpdate::dispatch($setting->toArray());
        } catch (BroadcastException $e) {
            // Reverb server may not be running — silently skip broadcast
            // The overlay will pick up changes next time it loads
            logger()->warning('Reverb broadcast failed (server may be offline): '.$e->getMessage());
        }
    }

    public function deleted(OverlaySetting $setting): void
    {
        Cache::forget(OverlayApiController::SETTINGS_CACHE_KEY);
    }
}
