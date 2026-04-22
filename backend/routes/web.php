<?php

use App\Http\Controllers\TwitchOAuthController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

// Twitch OAuth Authorization Code flow.
// /twitch/oauth/redirect opens the consent screen; Twitch calls back on /callback.
Route::prefix('twitch/oauth')->group(function () {
    Route::get('/redirect', [TwitchOAuthController::class, 'redirect'])->name('twitch.oauth.redirect');
    Route::get('/callback', [TwitchOAuthController::class, 'callback'])->name('twitch.oauth.callback');
});
