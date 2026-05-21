<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class EmploymentTypeSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();
        $rows = [
            ['code' => 'TETAP',       'name' => 'Tetap'],
            ['code' => 'KONTRAK',     'name' => 'Kontrak'],
            ['code' => 'MAGANG',      'name' => 'Magang'],
            ['code' => 'PARUH_WAKTU', 'name' => 'Paruh Waktu'],
        ];

        foreach ($rows as $row) {
            DB::table('employment_types')->insert([
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
