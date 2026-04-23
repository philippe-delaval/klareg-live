<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ticker_settings', function (Blueprint $table) {
            $table->id();

            // General
            $table->boolean('ticker_enabled')->default(true);
            $table->integer('ticker_speed')->default(60);

            // Messages
            $table->json('messages')->nullable();
            $table->json('scheduled_messages')->nullable();
            $table->json('priority_messages')->nullable();

            // Weather
            $table->boolean('weather_enabled')->default(false);
            $table->string('weather_city')->default('Paris');
            $table->string('weather_api_key')->nullable();
            $table->string('weather_units')->default('metric');

            // Music (Last.fm)
            $table->boolean('music_enabled')->default(false);
            $table->string('lastfm_username')->nullable();
            $table->string('lastfm_api_key')->nullable();

            // Twitch Events in Ticker
            $table->boolean('twitch_events_enabled')->default(false);
            $table->boolean('twitch_events_follow')->default(true);
            $table->boolean('twitch_events_sub')->default(true);

            // Crypto
            $table->boolean('crypto_enabled')->default(false);
            $table->json('crypto_symbols')->nullable();
            $table->integer('crypto_refresh_minutes')->default(5);

            // Stream Stats
            $table->boolean('stats_enabled')->default(false);

            // Countdown
            $table->boolean('countdown_ticker_enabled')->default(false);
            $table->dateTime('countdown_ticker_target')->nullable();
            $table->string('countdown_ticker_label')->nullable();

            // Chat Command
            $table->boolean('chat_command_enabled')->default(false);
            $table->string('chat_command_keyword')->default('!ticker');

            // Emergency
            $table->boolean('emergency_enabled')->default(false);
            $table->string('emergency_message')->nullable();
            $table->string('emergency_color')->default('#FF4444');

            // Scene Activation
            $table->boolean('scene_gaming_enabled')->default(true);
            $table->boolean('scene_brb_enabled')->default(false);
            $table->boolean('scene_starting_enabled')->default(true);
            $table->boolean('scene_chatting_enabled')->default(true);
            $table->boolean('scene_screenshare_enabled')->default(true);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ticker_settings');
    }
};
