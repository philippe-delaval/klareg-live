<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('overlay_settings', function (Blueprint $table) {
            $table->id();
            $table->string('channel_name')->default('Klareg');
            $table->string('starting_title')->default('Lancement imminent');
            $table->string('brb_message')->default('De retour bientôt');
            $table->string('accent_color')->default('#5B7FFF');
            $table->string('current_ticker')->default('Bienvenue sur le live ! Installez-vous confortablement.');
            $table->unsignedInteger('sub_goal')->default(200);
            $table->unsignedInteger('sub_current')->default(142);
            $table->unsignedInteger('follower_goal')->default(5000);
            $table->unsignedInteger('follower_current')->default(3840);
            $table->string('now_playing_track')->default('Synthwave Radio');
            $table->string('now_playing_artist')->default('Chill Beats');
            $table->string('stream_title')->default('Ranked Grind & Soirée Communautaire');
            $table->string('stream_category')->default('Just Chatting');
            $table->string('next_stream')->default('Demain à 18h00 CET');
            $table->unsignedInteger('countdown_minutes')->default(5);
            $table->unsignedInteger('countdown_seconds')->default(0);
            $table->unsignedInteger('brb_duration_minutes')->default(5);
            $table->json('socials')->nullable();
            $table->json('ticker_messages')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('overlay_settings');
    }
};
