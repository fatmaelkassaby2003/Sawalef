<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;

class UpdateUserCountriesSeeder extends Seeder
{
    public function run(): void
    {
        // تحديث المستخدمين الذين ليس لديهم دولة
        // تحديث المستخدمين الذين ليس لديهم دولة
        User::whereNull('country_en')->orWhereNull('country_ar')->update([
            'country_en' => 'Egypt',
            'country_ar' => 'مصر',
        ]);
    }
}