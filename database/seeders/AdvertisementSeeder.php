<?php

namespace Database\Seeders;

use App\Models\Advertisement;
use Illuminate\Database\Seeder;

class AdvertisementSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $ads = [
            [
                'image' => 'ads/placeholder1.png',
                'text_ar' => 'عرض خاص على الباقات الذهبية',
                'text_en' => 'Special offer on Golden Packages',
                'is_active' => true,
            ],
            [
                'image' => 'ads/placeholder2.png',
                'text_ar' => 'حمل التطبيق الآن واستمتع بمميزات حصرية',
                'text_en' => 'Download the app now and enjoy exclusive features',
                'is_active' => true,
            ],
            [
                'image' => 'ads/placeholder3.png',
                'text_ar' => 'انضم إلى مجتمعنا اليوم',
                'text_en' => 'Join our community today',
                'is_active' => false,
            ],
        ];

        foreach ($ads as $ad) {
            Advertisement::create($ad);
        }
    }
}
