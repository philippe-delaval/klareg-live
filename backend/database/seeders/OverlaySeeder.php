<?php

namespace Database\Seeders;

use App\Models\OverlaySetting;
use App\Models\Schedule;
use Illuminate\Database\Seeder;

class OverlaySeeder extends Seeder
{
    public function run(): void
    {
        // Create default settings
        OverlaySetting::create(OverlaySetting::defaults());

        // Create default schedule entries
        $schedules = [
            ['time' => '18:00', 'label' => 'Just Chatting / Échauffement', 'is_active' => true, 'sort_order' => 1],
            ['time' => '19:00', 'label' => 'Ranked Grind', 'is_active' => true, 'sort_order' => 2],
            ['time' => '22:00', 'label' => 'Jeux Communautaires', 'is_active' => false, 'sort_order' => 3],
        ];

        foreach ($schedules as $schedule) {
            Schedule::create($schedule);
        }
    }
}
