<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DocumentTypeSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();
        $rows = [
            ['code' => 'KTP',             'name' => 'KTP'],
            ['code' => 'NPWP',            'name' => 'NPWP'],
            ['code' => 'KK',              'name' => 'Kartu Keluarga'],
            ['code' => 'IJAZAH',          'name' => 'Ijazah Terakhir'],
            ['code' => 'SERTIFIKAT',      'name' => 'Sertifikat'],
            ['code' => 'KONTRAK',         'name' => 'Kontrak Kerja'],
            ['code' => 'SK_PENGANGKATAN', 'name' => 'SK Pengangkatan'],
            ['code' => 'FOTO',            'name' => 'Foto'],
        ];

        foreach ($rows as $row) {
            DB::table('document_types')->insert([
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
