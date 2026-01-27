<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class AdminContactSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        \App\Models\AdminContact::updateOrCreate(
            ['id' => 1],
            [
                'phone' => '+201234567890',
                'email' => 'admin@sawalef.com',
                'links' => [
                    ['name' => 'واتساب', 'url' => 'https://wa.me/201234567890'],
                    ['name' => 'فيسبوك', 'url' => 'https://facebook.com/sawalef'],
                    ['name' => 'إنستجرام', 'url' => 'https://instagram.com/sawalef'],
                ],
            ]
        );
    }
}
