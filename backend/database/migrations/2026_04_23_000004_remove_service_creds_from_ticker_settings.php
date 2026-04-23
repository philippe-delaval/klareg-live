<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ticker_settings', function (Blueprint $table) {
            $table->dropColumn([
                'spotify_client_id',
                'spotify_client_secret',
                'spotify_refresh_token',
                'weather_api_key',
                'weather_city',
                'weather_units',
            ]);
        });
    }

    public function down(): void
    {
        Schema::table('ticker_settings', function (Blueprint $table) {
            $table->string('spotify_client_id')->nullable();
            $table->string('spotify_client_secret')->nullable();
            $table->text('spotify_refresh_token')->nullable();
            $table->string('weather_api_key')->nullable();
            $table->string('weather_city')->default('Paris');
            $table->string('weather_units')->default('metric');
        });
    }
};
