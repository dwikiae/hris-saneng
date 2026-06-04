<?php

namespace App\Modules\Karyawan\Database\Seeders;

use App\Modules\Karyawan\Support\WilayahData;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ProvinceSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('provinces')->upsert(WilayahData::provinces(), ['code'], ['name', 'updated_at']);
    }
}
