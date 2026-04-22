<?php

use App\Http\Controllers\OverlayApiController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

// Overlay API (public — no auth required for OBS browser source access)
Route::prefix('overlay')->group(function () {
    Route::get('/settings', [OverlayApiController::class, 'settings']);
    Route::get('/schedule', [OverlayApiController::class, 'schedule']);
    Route::get('/reverb-config', [OverlayApiController::class, 'reverbConfig']);
});

// Twitch status API
Route::get('/twitch/status', [OverlayApiController::class, 'twitchStatus']);

// Health check — for load balancers and monitoring.
Route::get('/health', function () {
    $checks = ['database' => false, 'cache' => false];
    $healthy = true;

    try {
        DB::connection()->getPdo();
        $checks['database'] = true;
    } catch (Throwable) {
        $healthy = false;
    }

    try {
        Cache::put('health:probe', '1', 5);
        $checks['cache'] = Cache::get('health:probe') === '1';
        Cache::forget('health:probe');
    } catch (Throwable) {
        $healthy = false;
    }

    return response()->json([
        'status' => $healthy && $checks['database'] && $checks['cache'] ? 'ok' : 'degraded',
        'timestamp' => now()->toIso8601String(),
        'checks' => $checks,
        'version' => config('app.version', 'dev'),
    ], $healthy ? 200 : 503);
});
