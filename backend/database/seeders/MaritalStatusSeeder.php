<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class MaritalStatusSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();
        $rows = [
            ['code' => 'BELUM_MENIKAH', 'name' => 'Belum Menikah'],
            ['code' => 'MENIKAH',       'name' => 'Menikah'],
            ['code' => 'CERAI_HIDUP',   'name' => 'Cerai Hidup'],
            ['code' => 'CERAI_MATI',    'name' => 'Cerai Mati'],
        ];

        foreach ($rows as $row) {
            DB::table('marital_statuses')->insert([
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
