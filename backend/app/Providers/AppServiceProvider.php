<?php

namespace App\Providers;

use App\Models\OverlaySetting;
use App\Observers\OverlaySettingObserver;
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
    }
}
