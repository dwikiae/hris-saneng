<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class BankSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();
        $rows = [
            ['code' => 'BCA',        'name' => 'BCA'],
            ['code' => 'BRI',        'name' => 'BRI'],
            ['code' => 'BNI',        'name' => 'BNI'],
            ['code' => 'MANDIRI',    'name' => 'Mandiri'],
            ['code' => 'CIMB_NIAGA', 'name' => 'CIMB Niaga'],
            ['code' => 'DANAMON',    'name' => 'Danamon'],
            ['code' => 'PERMATA',    'name' => 'Permata'],
            ['code' => 'BTN',        'name' => 'BTN'],
            ['code' => 'BSI',        'name' => 'BSI'],
            ['code' => 'JENIUS',     'name' => 'Jenius'],
        ];

        foreach ($rows as $row) {
            DB::table('banks')->insert([
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
