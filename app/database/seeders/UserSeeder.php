<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        User::updateOrCreate(
            ['username' => 'N5569'],
            [
                'name' => 'N5569',
                'email' => 'n5569@example.com',
                'password' => 'abcd@1234',
            ]
        );
    }
}


