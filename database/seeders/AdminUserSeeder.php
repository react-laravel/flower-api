<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class AdminUserSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create admin user
        User::updateOrCreate(
            ['email' => 'admin@flower.com'],
            [
                'name' => '管理员',
                'email' => 'admin@flower.com',
                'password' => Hash::make(env('ADMIN_DEFAULT_PASSWORD', Str::random(24))),
                'is_admin' => true,
            ]
        );
    }
}
