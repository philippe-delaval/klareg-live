<?php

namespace App\Providers;

use App\Models\OverlaySetting;
use App\Models\TickerSetting;
use App\Observers\OverlaySettingObserver;
use App\Observers\TickerSettingObserver;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        OverlaySetting::observe(OverlaySettingObserver::class);
        TickerSetting::observe(TickerSettingObserver::class);
    }
}
