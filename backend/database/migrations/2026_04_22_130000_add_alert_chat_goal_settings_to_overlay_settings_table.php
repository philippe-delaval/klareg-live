<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('overlay_settings', function (Blueprint $table) {
            $table->boolean('alert_follow_enabled')->default(true);
            $table->integer('alert_follow_duration')->default(6);
            $table->boolean('alert_sub_enabled')->default(true);
            $table->integer('alert_sub_duration')->default(6);
            $table->boolean('alert_resub_enabled')->default(true);
            $table->integer('alert_resub_duration')->default(6);
            $table->boolean('alert_giftsub_enabled')->default(true);
            $table->integer('alert_giftsub_duration')->default(6);
            $table->boolean('alert_bits_enabled')->default(true);
            $table->integer('alert_bits_duration')->default(6);
            $table->integer('alert_bits_min_amount')->default(1);
            $table->boolean('alert_raid_enabled')->default(true);
            $table->integer('alert_raid_duration')->default(6);
            $table->integer('alert_raid_min_viewers')->default(1);
            $table->boolean('alert_donation_enabled')->default(true);
            $table->integer('alert_donation_duration')->default(6);
            $table->boolean('alert_hype_train_enabled')->default(true);
            $table->boolean('chat_enabled')->default(true);
            $table->integer('chat_max_messages')->default(50);
            $table->boolean('goal_sub_enabled')->default(true);
            $table->boolean('goal_follower_enabled')->default(true);
        });
    }

    public function down(): void
    {
        Schema::table('overlay_settings', function (Blueprint $table) {
            $table->dropColumn([
                'alert_follow_enabled', 'alert_follow_duration',
                'alert_sub_enabled', 'alert_sub_duration',
                'alert_resub_enabled', 'alert_resub_duration',
                'alert_giftsub_enabled', 'alert_giftsub_duration',
                'alert_bits_enabled', 'alert_bits_duration', 'alert_bits_min_amount',
                'alert_raid_enabled', 'alert_raid_duration', 'alert_raid_min_viewers',
                'alert_donation_enabled', 'alert_donation_duration',
                'alert_hype_train_enabled',
                'chat_enabled', 'chat_max_messages',
                'goal_sub_enabled', 'goal_follower_enabled',
            ]);
        });
    }
};
