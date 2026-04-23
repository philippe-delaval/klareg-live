<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('service_connections', function (Blueprint $table) {
            $table->id();

            // Spotify
            $table->boolean('spotify_enabled')->default(false);
            $table->string('spotify_client_id')->nullable();
            $table->string('spotify_client_secret')->nullable();
            $table->text('spotify_refresh_token')->nullable();
            $table->timestamp('spotify_connected_at')->nullable();

            // OpenWeatherMap
            $table->boolean('weather_enabled')->default(false);
            $table->string('weather_api_key')->nullable();
            $table->string('weather_city')->default('Paris');
            $table->string('weather_units')->default('metric');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('service_connections');
    }
};
