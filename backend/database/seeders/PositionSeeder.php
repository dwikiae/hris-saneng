<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PositionSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();
        $rows = [
            ['code' => 'DIREKTUR',   'name' => 'Direktur'],
            ['code' => 'MANAGER',    'name' => 'Manager'],
            ['code' => 'SUPERVISOR', 'name' => 'Supervisor'],
            ['code' => 'STAFF',      'name' => 'Staff'],
            ['code' => 'ADMIN',      'name' => 'Admin'],
            ['code' => 'OPERATOR',   'name' => 'Operator'],
            ['code' => 'TEKNISI',    'name' => 'Teknisi'],
            ['code' => 'DRIVER',     'name' => 'Driver'],
            ['code' => 'SECURITY',   'name' => 'Security'],
            ['code' => 'OB',         'name' => 'OB'],
        ];

        foreach ($rows as $row) {
            DB::table('positions')->insert([
                'company_id' => 1,
                'code'       => $row['code'],
                'name'       => $row['name'],
                'is_active'  => true,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }
}
