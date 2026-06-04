<?php

namespace App\Modules\Karyawan\Database\Seeders;

use App\Modules\Karyawan\Support\CountryData;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CountrySeeder extends Seeder
{
    public function run(): void
    {
        foreach (array_chunk(CountryData::countries(), 100) as $chunk) {
            DB::table('countries')->upsert($chunk, ['code'], ['name', 'is_active', 'updated_at']);
        }
    }
}
