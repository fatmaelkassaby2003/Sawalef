<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Package;

class PackageSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $packages = [
            [
                'name' => 'باقة المبتدئين 🌟',
                'description' => 'باقة رائعة للبدء مع عدد جيد من الجواهر',
                'gems' => 100,
                'price' => 50.00,
                'is_active' => true,
                'order' => 1,
            ],
            [
                'name' => 'الباقة الفضية 💫',
                'description' => 'باقة شائعة مع قيمة ممتازة مقابل السعر',
                'gems' => 250,
                'price' => 100.00,
                'is_active' => true,
                'order' => 2,
            ],
            [
                'name' => 'الباقة الذهبية ⭐',
                'description' => 'باقة مميزة مع مكافأة إضافية من الجواهر',
                'gems' => 550,
                'price' => 200.00,
                'is_active' => true,
                'order' => 3,
            ],
            [
                'name' => 'باقة البلاتين 💎',
                'description' => 'أفضل قيمة! احصل على جواهر إضافية مجاناً',
                'gems' => 1200,
                'price' => 400.00,
                'is_active' => true,
                'order' => 4,
            ],
            [
                'name' => 'الباقة الماسية 👑',
                'description' => 'الباقة الأعلى قيمة مع أكبر عدد من الجواهر!',
                'gems' => 3000,
                'price' => 900.00,
                'is_active' => true,
                'order' => 5,
            ],
        ];

        foreach ($packages as $package) {
            Package::create($package);
        }

        $this->command->info('تم إنشاء ' . count($packages) . ' باقات بنجاح! 🎉');
    }
}
