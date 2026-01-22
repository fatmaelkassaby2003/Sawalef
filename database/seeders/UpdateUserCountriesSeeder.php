<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;

class UpdateUserCountriesSeeder extends Seeder
{
    public function run(): void
    {
        // تحديث المستخدمين الذين ليس لديهم دولة
        User::whereNull('country')->update([
            'country' => 'Egypt' // أو أي دولة افتراضية
        ]);
    }
}