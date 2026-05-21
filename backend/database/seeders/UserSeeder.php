<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $companyId = DB::table('companies')->value('id');

        DB::table('users')->insertOrIgnore([
            'company_id'           => $companyId,
            'name'                 => 'System Admin',
            'email'                => 'admin@saneng.co.id',
            'password'             => Hash::make('Admin@1234'),
            'language_preference'  => 'id',
            'force_password_reset' => true,
            'login_attempts'       => 0,
            'created_at'           => now(),
            'updated_at'           => now(),
        ]);
    }
}
