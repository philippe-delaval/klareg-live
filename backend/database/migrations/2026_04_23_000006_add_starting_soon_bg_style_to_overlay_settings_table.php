<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('overlay_settings', function (Blueprint $table) {
            $table->string('starting_soon_bg_style')->default('aurora')->after('starting_title');
        });
    }

    public function down(): void
    {
        Schema::table('overlay_settings', function (Blueprint $table) {
            $table->dropColumn('starting_soon_bg_style');
        });
    }
};
