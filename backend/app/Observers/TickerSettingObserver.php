<?php

namespace App\Observers;

use App\Events\BroadcastOverlayUpdate;
use App\Models\TickerSetting;
use Illuminate\Support\Facades\Cache;

class TickerSettingObserver
{
    public function saved(TickerSetting $setting): void
    {
        Cache::forget('ticker:compiled:all');

        try {
            BroadcastOverlayUpdate::dispatch(array_merge(
                ['type' => 'ticker_setting_saved'],
                $setting->toArray()
            ));
        } catch (\Throwable $e) {
            logger()->warning('Reverb broadcast failed (TickerSetting): '.$e->getMessage());
        }
    }
}
