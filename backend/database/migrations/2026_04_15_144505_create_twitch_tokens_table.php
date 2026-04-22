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
        Schema::create('twitch_tokens', function (Blueprint $table) {
            $table->id();
            $table->string('access_token');
            $table->string('refresh_token')->nullable();
            $table->timestamp('expires_at');
            $table->string('token_type')->default('app_access');
            $table->json('scope')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('twitch_tokens');
    }
};
