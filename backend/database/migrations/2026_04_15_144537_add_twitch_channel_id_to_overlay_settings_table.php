<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('overlay_settings', function (Blueprint $table) {
            $table->string('twitch_channel_id')->nullable()->after('channel_name');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('overlay_settings', function (Blueprint $table) {
            $table->dropColumn('twitch_channel_id');
        });
    }
};
