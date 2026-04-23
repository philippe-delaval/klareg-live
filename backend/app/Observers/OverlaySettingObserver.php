<?php

namespace App\Observers;

use App\Events\BroadcastOverlayUpdate;
use App\Http\Controllers\OverlayApiController;
use App\Models\OverlaySetting;
use Illuminate\Support\Facades\Cache;

class OverlaySettingObserver
{
    public function saved(OverlaySetting $setting): void
    {
        Cache::forget(OverlayApiController::SETTINGS_CACHE_KEY);

        try {
            BroadcastOverlayUpdate::dispatch($setting->toArray());
        } catch (\Throwable $e) {
            logger()->warning('Reverb broadcast failed (server may be offline): '.$e->getMessage());
        }
    }

    public function deleted(OverlaySetting $setting): void
    {
        Cache::forget(OverlayApiController::SETTINGS_CACHE_KEY);
    }
}
