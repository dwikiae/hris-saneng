<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class BloodTypeSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();
        $rows = [
            ['code' => 'A',      'name' => 'A'],
            ['code' => 'B',      'name' => 'B'],
            ['code' => 'AB',     'name' => 'AB'],
            ['code' => 'O',      'name' => 'O'],
            ['code' => 'A_POS',  'name' => 'A+'],
            ['code' => 'A_NEG',  'name' => 'A-'],
            ['code' => 'B_POS',  'name' => 'B+'],
            ['code' => 'B_NEG',  'name' => 'B-'],
            ['code' => 'AB_POS', 'name' => 'AB+'],
            ['code' => 'AB_NEG', 'name' => 'AB-'],
            ['code' => 'O_POS',  'name' => 'O+'],
            ['code' => 'O_NEG',  'name' => 'O-'],
        ];

        foreach ($rows as $row) {
            DB::table('blood_types')->insert([
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
