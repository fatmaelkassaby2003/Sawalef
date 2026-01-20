<?php

namespace Database\Seeders;

use App\Models\Hobby;
use Illuminate\Database\Seeder;

class HobbySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $hobbies = [
            ['name' => 'القراءة', 'icon' => '📚'],
            ['name' => 'الكتابة', 'icon' => '✍️'],
            ['name' => 'الرياضة', 'icon' => '⚽'],
            ['name' => 'السباحة', 'icon' => '🏊'],
            ['name' => 'كرة القدم', 'icon' => '⚽'],
            ['name' => 'السفر', 'icon' => '✈️'],
            ['name' => 'الطبخ', 'icon' => '🍳'],
            ['name' => 'الرسم', 'icon' => '🎨'],
            ['name' => 'التصوير', 'icon' => '📷'],
            ['name' => 'الموسيقى', 'icon' => '🎵'],
            ['name' => 'البرمجة', 'icon' => '💻'],
            ['name' => 'الألعاب الإلكترونية', 'icon' => '🎮'],
            ['name' => 'مشاهدة الأفلام', 'icon' => '🎬'],
            ['name' => 'التصميم', 'icon' => '🎨'],
            ['name' => 'البستنة', 'icon' => '🌱'],
        ];

        foreach ($hobbies as $hobby) {
            Hobby::create($hobby);
        }
    }
}
