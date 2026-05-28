<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        User::withoutCompanyScope()->updateOrCreate(
            ['email' => 'admin@saneng.co.id'],
            [
                'company_id' => null,
                'name' => 'System Administrator',
                'password' => Hash::make('Admin@12345'),
                'force_password_reset' => true,
                'language_preference' => 'id',
            ]
        );
    }
}
