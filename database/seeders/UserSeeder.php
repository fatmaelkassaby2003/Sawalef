<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Hobby;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create test users
        $users = [
            [
                'name' => 'أحمد محمد',
                'phone' => '01234567891',
                'nickname' => 'أحمد',
                'age' => 25,
                'country' => 'مصر',
                'gender' => 'male',
            ],
            [
                'name' => 'فاطمة علي',
                'phone' => '01234567892',
                'nickname' => 'فطوم',
                'age' => 23,
                'country' => 'السعودية',
                'gender' => 'female',
            ],
            [
                'name' => 'محمد عبدالله',
                'phone' => '01234567893',
                'nickname' => 'محمد',
                'age' => 28,
                'country' => 'الإمارات',
                'gender' => 'male',
            ],
            [
                'name' => 'سارة خالد',
                'phone' => '01234567894',
                'nickname' => 'سارة',
                'age' => 22,
                'country' => 'مصر',
                'gender' => 'female',
            ],
            [
                'name' => 'عمر حسن',
                'phone' => '01234567895',
                'nickname' => 'عمر',
                'age' => 30,
                'country' => 'الكويت',
                'gender' => 'male',
            ],
        ];

        $allHobbyIds = Hobby::pluck('id')->toArray();

        foreach ($users as $userData) {
            $user = User::create($userData);
            
            // Assign random hobbies (3-7 hobbies per user)
            $hobbyCount = rand(3, 7);
            $randomHobbies = array_rand(array_flip($allHobbyIds), $hobbyCount);
            $user->hobbies()->attach($randomHobbies);
        }
    }
}
