<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DepartmentSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();
        $rows = [
            ['code' => 'DIREKSI',    'name' => 'Direksi'],
            ['code' => 'HRD',        'name' => 'HRD'],
            ['code' => 'KEUANGAN',   'name' => 'Keuangan'],
            ['code' => 'OPERASIONAL','name' => 'Operasional'],
            ['code' => 'LOGISTIK',   'name' => 'Logistik'],
            ['code' => 'PURCHASING', 'name' => 'Purchasing'],
            ['code' => 'MARKETING',  'name' => 'Marketing'],
            ['code' => 'IT',         'name' => 'IT'],
            ['code' => 'LEGAL',      'name' => 'Legal'],
            ['code' => 'UMUM',       'name' => 'Umum'],
        ];

        foreach ($rows as $row) {
            DB::table('departments')->insert([
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
