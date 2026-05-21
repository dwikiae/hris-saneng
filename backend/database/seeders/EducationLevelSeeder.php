<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class EducationLevelSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();
        $rows = [
            ['code' => 'SD',      'name' => 'SD'],
            ['code' => 'SMP',     'name' => 'SMP'],
            ['code' => 'SMA_SMK', 'name' => 'SMA/SMK'],
            ['code' => 'D1',      'name' => 'D1'],
            ['code' => 'D2',      'name' => 'D2'],
            ['code' => 'D3',      'name' => 'D3'],
            ['code' => 'S1',      'name' => 'S1'],
            ['code' => 'S2',      'name' => 'S2'],
            ['code' => 'S3',      'name' => 'S3'],
        ];

        foreach ($rows as $row) {
            DB::table('education_levels')->insert([
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
