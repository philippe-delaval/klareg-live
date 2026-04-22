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
        Schema::create('twitch_event_subscriptions', function (Blueprint $table) {
            $table->id();
            $table->string('subscription_id')->unique();
            $table->string('type');
            $table->string('version');
            $table->json('condition');
            $table->string('status')->default('enabled');
            $table->string('session_id')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('twitch_event_subscriptions');
    }
};
