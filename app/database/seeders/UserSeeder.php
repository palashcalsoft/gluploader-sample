<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => 'user@admin.com'],
            [
                'name' => 'Admin User',
                'password' => 'Abcd@1234',
            ]
        );
    }
}


