<?php

namespace Database\Seeders;

use App\Modules\Karyawan\Database\Seeders\CitySeeder;
use App\Modules\Karyawan\Database\Seeders\CountrySeeder;
use App\Modules\Karyawan\Database\Seeders\EmployeeDefaultMasterDataSeeder;
use App\Modules\Karyawan\Database\Seeders\ProvinceSeeder;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            InstanceSettingsSeeder::class,
            ProvinceSeeder::class,
            CitySeeder::class,
            CountrySeeder::class,
            CompanySeeder::class,
            EmployeeDefaultMasterDataSeeder::class,
            CompanySettingsSeeder::class,
            PermissionSeeder::class,
            RoleSeeder::class,
            AdminUserSeeder::class,
            PlatformAdministratorAssignmentSeeder::class,
            MaritalStatusSeeder::class,
            BloodTypeSeeder::class,
            EmployeeSeeder::class,
        ]);
    }
}
