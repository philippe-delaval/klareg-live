<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Widen access_token / refresh_token to TEXT so Laravel's "encrypted" cast
     * (which produces payloads much longer than the raw token) fits.
     *
     * NOTE: Existing rows (if any) must be re-encrypted before deploying the
     * "encrypted" cast — run `php artisan twitch:token:reencrypt` or simply
     * truncate `twitch_tokens` and let the app fetch a fresh app-access-token.
     */
    public function up(): void
    {
        Schema::table('twitch_tokens', function (Blueprint $table) {
            $table->text('access_token')->change();
            $table->text('refresh_token')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('twitch_tokens', function (Blueprint $table) {
            $table->string('access_token')->change();
            $table->string('refresh_token')->nullable()->change();
        });
    }
};
