<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('service_connections', function (Blueprint $table) {
            $table->string('coingecko_api_key')->nullable()->after('weather_units');
        });
    }

    public function down(): void
    {
        Schema::table('service_connections', function (Blueprint $table) {
            $table->dropColumn('coingecko_api_key');
        });
    }
};
