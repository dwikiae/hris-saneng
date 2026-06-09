<?php

namespace App\Modules\Karyawan\Database\Seeders;

use App\Modules\Karyawan\Application\EmployeeDefaultMasterDataService;
use Illuminate\Database\Seeder;

class EmployeeDefaultMasterDataSeeder extends Seeder
{
    public function run(): void
    {
        app(EmployeeDefaultMasterDataService::class)->seedForAllCompanies();
    }
}
