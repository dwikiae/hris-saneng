<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ReligionSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();
        $rows = [
            ['code' => 'ISLAM',             'name' => 'Islam'],
            ['code' => 'KRISTEN_PROTESTAN', 'name' => 'Kristen Protestan'],
            ['code' => 'KATOLIK',           'name' => 'Katolik'],
            ['code' => 'HINDU',             'name' => 'Hindu'],
            ['code' => 'BUDDHA',            'name' => 'Buddha'],
            ['code' => 'KONGHUCU',          'name' => 'Konghucu'],
        ];

        foreach ($rows as $row) {
            DB::table('religions')->insert([
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
