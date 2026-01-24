<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class AdminUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        \App\Models\User::firstOrCreate(
            ['email' => 'admin@sawalef.com'],
            [
                'name' => 'Admin',
                'password' => bcrypt('password'), // Change this in production
                'phone' => '0000000000',
                'is_admin' => true,
                'country_en' => 'Egypt',
                'country_ar' => 'مصر',
            ]
        );
    }
}
