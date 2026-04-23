<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('overlay_settings', function (Blueprint $table) {
            $table->string('brb_bg_style')->default('none')->after('brb_message');
            $table->string('ending_bg_style')->default('none')->after('brb_bg_style');
            $table->json('color_presets')->nullable()->after('overlay_theme');
        });
    }

    public function down(): void
    {
        Schema::table('overlay_settings', function (Blueprint $table) {
            $table->dropColumn(['brb_bg_style', 'ending_bg_style', 'color_presets']);
        });
    }
};
