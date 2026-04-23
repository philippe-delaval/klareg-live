<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ticker_settings', function (Blueprint $table) {
            $table->dropColumn(['lastfm_username', 'lastfm_api_key']);

            $table->string('spotify_client_id')->nullable()->after('music_enabled');
            $table->string('spotify_client_secret')->nullable()->after('spotify_client_id');
            $table->text('spotify_refresh_token')->nullable()->after('spotify_client_secret');
        });
    }

    public function down(): void
    {
        Schema::table('ticker_settings', function (Blueprint $table) {
            $table->dropColumn(['spotify_client_id', 'spotify_client_secret', 'spotify_refresh_token']);

            $table->string('lastfm_username')->nullable();
            $table->string('lastfm_api_key')->nullable();
        });
    }
};
