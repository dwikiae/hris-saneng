<?php

namespace App\Modules\Karyawan\Database\Seeders;

use App\Modules\Karyawan\Support\WilayahData;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CitySeeder extends Seeder
{
    public function run(): void
    {
        foreach (array_chunk(WilayahData::cities(), 100) as $chunk) {
            DB::table('cities')->upsert($chunk, ['code'], ['province_code', 'name', 'updated_at']);
        }
    }
}
