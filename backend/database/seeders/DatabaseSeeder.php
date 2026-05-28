<?php

namespace Database\Seeders;

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
            CompanySeeder::class,
            CompanySettingsSeeder::class,
            PermissionSeeder::class,
            RoleSeeder::class,
            AdminUserSeeder::class,
            DepartmentSeeder::class,
            PositionSeeder::class,
            EmploymentTypeSeeder::class,
            EducationLevelSeeder::class,
            ReligionSeeder::class,
            MaritalStatusSeeder::class,
            BloodTypeSeeder::class,
            BankSeeder::class,
            DocumentTypeSeeder::class,
            EmployeeSeeder::class,
        ]);
    }
}
